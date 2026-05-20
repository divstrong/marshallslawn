/**
 * Foreman GPS tracking. Wraps `expo-location` background updates so the
 * office can see crews on the Dispatch map. No-ops on web.
 */

import * as Location from 'expo-location';
import { Platform } from 'react-native';

import { LOCATION_TASK } from '@/lib/location-task';

export type TrackingPermission = 'granted' | 'denied' | 'undetermined';

const isWeb = Platform.OS === 'web';

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

/** Begin background location updates (idempotent). */
export async function startTracking(): Promise<void> {
  if (isWeb || (await isTrackingActive())) {
    return;
  }
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

export async function stopTracking(): Promise<void> {
  if (isWeb) {
    return;
  }
  if (await isTrackingActive()) {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  }
}
