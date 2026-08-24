/**
 * Foreman GPS tracking. Wraps `expo-location` background updates so the
 * office can see crews on the Dispatch map. No-ops on web.
 */

import * as Location from 'expo-location';
import { AppState, Platform } from 'react-native';

import { LOCATION_TASK } from '@/lib/location-task';
import { trace } from '@/lib/monitoring';

export type TrackingPermission = 'granted' | 'denied' | 'undetermined';

const isWeb = Platform.OS === 'web';

/** An outstanding `AppState` listener waiting to start tracking, if any. */
let pendingStart: { remove: () => void } | null = null;

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
  const background = await Location.getBackgroundPermissionsAsync();
  if (background.granted) {
    return 'granted';
  }
  return background.status === 'denied' && !background.canAskAgain ? 'denied' : 'undetermined';
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
  if (background.granted) {
    return 'granted';
  }
  return background.canAskAgain ? 'undetermined' : 'denied';
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
 * Begin background location updates (idempotent).
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
