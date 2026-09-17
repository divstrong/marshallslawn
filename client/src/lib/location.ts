/**
 * Crew GPS tracking. Wraps `expo-location` so the office can see crews on the
 * Dispatch map. No-ops on web.
 *
 * The two platforms take different routes, and the difference is store policy,
 * not a technical one:
 *
 * - **Android** reports continuously in the background, via a TaskManager task
 *   and a foreground service. Play permits this with the prominent disclosure
 *   in `components/location-disclosure.tsx`.
 * - **iOS** reports only while the app is on screen. App Review rejected build
 *   1.0 (22) under guideline 2.5.4 — tracking employees is not an accepted
 *   reason to declare the `location` background mode — so the app no longer
 *   declares it, no longer asks for "Always", and watches position from the
 *   foreground instead. Dispatch gets a fresh fix whenever a crew has the app
 *   open (which they do at every stop, to work the job clock) and a "last
 *   known position" pin in between.
 */

import * as Location from 'expo-location';
import { AppState, Platform } from 'react-native';

import { LOCATION_ALWAYS_ASKED_KEY } from '@/constants/config';
import { reportLocations } from '@/lib/location-report';
import { LOCATION_TASK } from '@/lib/location-task';
import { trace } from '@/lib/monitoring';
import { getItem, setItem } from '@/lib/storage';

/**
 * `granted` is the full feature for the platform in hand: "Always" on Android,
 * "While Using" on iOS, which is all iOS asks for now. `whenInUse` is the
 * degraded Android grant — tracking runs, but the OS won't relaunch the app
 * after it's killed or the phone restarts, so dispatch can lose the crew until
 * the app is reopened. iOS never reports it.
 */
export type TrackingPermission = 'granted' | 'whenInUse' | 'denied' | 'undetermined';

/** True when tracking can run at all, fully or degraded. */
export function canTrack(permission: TrackingPermission): boolean {
  return permission === 'granted' || permission === 'whenInUse';
}

const isWeb = Platform.OS === 'web';
const isIos = Platform.OS === 'ios';

/** Report at most this often, and only after moving at least this far. */
const REPORT_INTERVAL_MS = 60_000;
const REPORT_DISTANCE_M = 40;

/** An outstanding `AppState` listener waiting to start tracking, if any. */
let pendingStart: { remove: () => void } | null = null;

/**
 * Classify the background half of the permission once foreground is granted.
 * Android reports a "While Using"-only grant as background *not* granted, and
 * the OS status alone can't say whether that's because the "Always" prompt was
 * declined or never shown — hence the persisted asked-flag, see
 * `LOCATION_ALWAYS_ASKED_KEY`. Android-only; iOS no longer asks.
 */
async function classifyBackground(background: {
  granted: boolean;
  status: Location.PermissionStatus;
  canAskAgain: boolean;
}): Promise<TrackingPermission> {
  if (background.granted) {
    return 'granted';
  }
  const asked = (await getItem(LOCATION_ALWAYS_ASKED_KEY)) === '1';
  if (asked || (background.status === 'denied' && !background.canAskAgain)) {
    return 'whenInUse';
  }
  return 'undetermined';
}

/** Current permission state without prompting. */
export async function getTrackingPermission(): Promise<TrackingPermission> {
  if (isWeb) {
    return 'denied';
  }
  const foreground = await Location.getForegroundPermissionsAsync();
  if (!foreground.granted) {
    return foreground.status === 'denied' && !foreground.canAskAgain
      ? 'denied'
      : 'undetermined';
  }
  // On iOS the foreground grant is the whole permission the app needs.
  if (isIos) {
    return 'granted';
  }
  return classifyBackground(await Location.getBackgroundPermissionsAsync());
}

/**
 * Prompt for permission: "While Using" on iOS, then additionally "Always" on
 * Android, where background reporting is the feature.
 */
export async function requestTrackingPermission(): Promise<TrackingPermission> {
  if (isWeb) {
    return 'denied';
  }
  const foreground = await Location.requestForegroundPermissionsAsync();
  if (!foreground.granted) {
    return foreground.canAskAgain ? 'undetermined' : 'denied';
  }
  if (isIos) {
    return 'granted';
  }
  const background = await Location.requestBackgroundPermissionsAsync();
  // Recorded regardless of the answer: the OS won't offer this prompt again.
  await setItem(LOCATION_ALWAYS_ASKED_KEY, '1');
  return classifyBackground(background);
}

export async function isTrackingActive(): Promise<boolean> {
  if (isWeb) {
    return false;
  }
  if (isIos) {
    return watcher !== null || startingWatch;
  }
  try {
    return await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK);
  } catch {
    return false;
  }
}

/**
 * Begin reporting position (idempotent). On Android this works on either a
 * full "Always" grant or a "While Using" one: the foreground service below is
 * what carries a while-in-use grant into the background (expo-location only
 * insists on the background permission when no foreground service is
 * configured).
 *
 * On Android the start is deferred until the app is actually in the
 * foreground. Android 12 forbids starting a foreground service from the
 * background and throws `ForegroundServiceStartNotAllowedException` when an
 * app tries, which takes the whole process down — and this app walks straight
 * into that window: on Android 11+ granting "Allow all the time" sends the
 * user out to the system settings page, so `requestTrackingPermission()` can
 * resolve and land here while the app is still backgrounded.
 *
 * iOS has no equivalent restriction, and its watcher only runs while the app
 * is on screen anyway, so it starts immediately.
 */
export async function startTracking(): Promise<void> {
  if (isWeb || (await isTrackingActive())) {
    return;
  }

  if (isIos) {
    await startForegroundWatch();
    return;
  }

  if (AppState.currentState !== 'active') {
    deferStartUntilForeground();
    return;
  }

  await beginLocationUpdates();
}

/** The Android `expo-location` call, once it is safe to make it. */
async function beginLocationUpdates(): Promise<void> {
  await Location.startLocationUpdatesAsync(LOCATION_TASK, {
    accuracy: Location.Accuracy.Balanced,
    timeInterval: REPORT_INTERVAL_MS,
    distanceInterval: REPORT_DISTANCE_M,
    deferredUpdatesInterval: REPORT_INTERVAL_MS,
    pausesUpdatesAutomatically: false,
    showsBackgroundLocationIndicator: true,
    foregroundService: {
      notificationTitle: "Marshall's Lawn",
      notificationBody: 'Sharing your location with dispatch while on the clock.',
      notificationColor: '#e00a35',
    },
  });
}

/* ------------------------------ iOS watcher ------------------------------ */

let watcher: Location.LocationSubscription | null = null;
let watcherAppState: { remove: () => void } | null = null;

/**
 * Held across the `await` in `startForegroundWatch`, so two overlapping starts
 * — the sign-in effect and the permission prompt both call `startTracking` —
 * can't leave two watchers running, and a `stopTracking` that lands mid-start
 * isn't undone by the start finishing afterwards.
 */
let startingWatch = false;

/**
 * Positions seen since the last POST. iOS ignores `timeInterval` on
 * `watchPositionAsync`, so a truck at road speed clears the 40 m threshold
 * every few seconds; buffering and flushing on our own clock holds that to one
 * request a minute while still handing dispatch the whole trail.
 */
let pending: Location.LocationObject[] = [];
let lastFlushAt = 0;

/** Roughly a minute of updates at highway speed, with room to spare. */
const MAX_PENDING = 120;

async function startForegroundWatch(): Promise<void> {
  if (startingWatch || watcher) {
    return;
  }
  startingWatch = true;

  let subscription: Location.LocationSubscription;
  try {
    subscription = await Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.Balanced,
        timeInterval: REPORT_INTERVAL_MS,
        distanceInterval: REPORT_DISTANCE_M,
      },
      (point) => {
        pending.push(point);
        if (pending.length > MAX_PENDING) {
          pending = pending.slice(-MAX_PENDING);
        }
        if (Date.now() - lastFlushAt >= REPORT_INTERVAL_MS) {
          void flushPending();
        }
      },
    );
  } catch (error) {
    startingWatch = false;
    throw error;
  }

  // `stopTracking` clears the flag; honour it rather than leaving a watcher
  // running for a foreman who has already signed out or paused sharing. Drop
  // anything the callback buffered in the meantime too — those points belong
  // to the session that just ended, and must not be posted under the next
  // foreman's token.
  if (!startingWatch) {
    subscription.remove();
    pending = [];
    return;
  }

  watcher = subscription;
  startingWatch = false;

  // iOS suspends us on the way out, and the position at that moment is the
  // freshest dispatch will get until the crew reopens the app — so send it.
  watcherAppState = AppState.addEventListener('change', (state) => {
    if (state !== 'active') {
      void flushPending();
    }
  });
}

function stopForegroundWatch(): void {
  // Also cancels an in-flight `startForegroundWatch`, which re-checks this.
  startingWatch = false;
  watcher?.remove();
  watcher = null;
  watcherAppState?.remove();
  watcherAppState = null;
  pending = [];
  lastFlushAt = 0;
}

/** Send everything buffered. `reportLocations` swallows its own failures. */
async function flushPending(): Promise<void> {
  if (pending.length === 0) {
    return;
  }
  const batch = pending;
  pending = [];
  lastFlushAt = Date.now();
  await reportLocations(batch);
}

/* ---------------------------- Android deferral ---------------------------- */

/**
 * Retry the start the moment the app returns to the foreground. Only ever one
 * of these is outstanding, and `stopTracking` cancels it — otherwise signing
 * out while the settings page is open would start tracking for whoever signs
 * in next.
 */
function deferStartUntilForeground(): void {
  cancelPendingStart();
  // Confirms from real devices whether this window is being hit at all.
  trace('location.start_deferred', { appState: AppState.currentState });

  pendingStart = AppState.addEventListener('change', (state) => {
    if (state !== 'active') {
      return;
    }
    cancelPendingStart();
    // Re-enter rather than starting directly: permission may have been
    // revoked, or tracking may have started by another route, while we waited.
    void startTracking().catch(() => {
      // Same swallow as the callers': a failed start is not worth a crash.
    });
  });
}

function cancelPendingStart(): void {
  pendingStart?.remove();
  pendingStart = null;
}

export async function stopTracking(): Promise<void> {
  if (isWeb) {
    return;
  }
  cancelPendingStart();

  if (isIos) {
    stopForegroundWatch();
    return;
  }

  if (await isTrackingActive()) {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  }
}
