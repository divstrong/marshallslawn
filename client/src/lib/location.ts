/**
 * Foreman GPS tracking. Wraps `expo-location` background updates so the
 * office can see crews on the Dispatch map. No-ops on web.
 */

import * as Location from 'expo-location';
import { AppState, Platform } from 'react-native';

import { LOCATION_ALWAYS_ASKED_KEY } from '@/constants/config';
import { LOCATION_TASK } from '@/lib/location-task';
import { trace } from '@/lib/monitoring';
import { getItem, setItem } from '@/lib/storage';

/**
 * `granted` is "Always" — the full feature. `whenInUse` is the degraded grant:
 * tracking runs, but the OS won't relaunch the app after it's killed or the
 * phone restarts, so dispatch can lose the crew until the app is reopened.
 */
export type TrackingPermission = 'granted' | 'whenInUse' | 'denied' | 'undetermined';

/** True when tracking can run at all, fully or degraded. */
export function canTrack(permission: TrackingPermission): boolean {
  return permission === 'granted' || permission === 'whenInUse';
}

const isWeb = Platform.OS === 'web';

/** An outstanding `AppState` listener waiting to start tracking, if any. */
let pendingStart: { remove: () => void } | null = null;

/**
 * Classify the background half of the permission once foreground is granted.
 * Both platforms report a "While Using"-only grant as background *not
 * granted*, and the OS status alone can't say whether that's because the
 * "Always" prompt was declined or never shown — hence the persisted
 * asked-flag, see `LOCATION_ALWAYS_ASKED_KEY`.
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
  return classifyBackground(await Location.getBackgroundPermissionsAsync());
}

/** Prompt for foreground then background ("Always") permission. */
export async function requestTrackingPermission(): Promise<TrackingPermission> {
  if (isWeb) {
    return 'denied';
  }
  const foreground = await Location.requestForegroundPermissionsAsync();
  if (!foreground.granted) {
    return foreground.canAskAgain ? 'undetermined' : 'denied';
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
  try {
    return await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK);
  } catch {
    return false;
  }
}

/**
 * Begin background location updates (idempotent). Works on either a full
 * "Always" grant or a "While Using" one: iOS keeps delivering updates in the
 * background for a session started while the app was on screen, and on
 * Android the foreground service below is what carries a while-in-use grant
 * into the background (expo-location only insists on the background
 * permission when no foreground service is configured).
 *
 * On Android the start is deferred until the app is actually in the
 * foreground. Android 12 forbids starting a foreground service from the
 * background and throws `ForegroundServiceStartNotAllowedException` when an
 * app tries, which takes the whole process down — and this app walks straight
 * into that window: on Android 11+ granting "Allow all the time" sends the
 * user out to the system settings page, so `requestTrackingPermission()` can
 * resolve and land here while the app is still backgrounded.
 *
 * iOS has no equivalent restriction, so it starts immediately as before.
 */
export async function startTracking(): Promise<void> {
  if (isWeb || (await isTrackingActive())) {
    return;
  }

  if (Platform.OS === 'android' && AppState.currentState !== 'active') {
    deferStartUntilForeground();
    return;
  }

  await beginLocationUpdates();
}

/** The actual `expo-location` call, once it is safe to make it. */
async function beginLocationUpdates(): Promise<void> {
  await Location.startLocationUpdatesAsync(LOCATION_TASK, {
    accuracy: Location.Accuracy.Balanced,
    timeInterval: 60_000,
    distanceInterval: 40,
    deferredUpdatesInterval: 60_000,
    pausesUpdatesAutomatically: false,
    showsBackgroundLocationIndicator: true,
    foregroundService: {
      notificationTitle: "Marshall's Lawn",
      notificationBody: 'Sharing your location with dispatch while on the clock.',
      notificationColor: '#e00a35',
    },
  });
}

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
  if (await isTrackingActive()) {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  }
}
