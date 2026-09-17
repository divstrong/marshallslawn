/**
 * Android background location task. Defined at module scope so it is
 * registered whenever the JS bundle loads — including when the OS wakes the
 * app to deliver a location update. Import this once from the root layout.
 *
 * iOS never starts this task: the app no longer declares the `location`
 * background mode (App Store guideline 2.5.4), and reports position from the
 * foreground watcher in `location.ts` instead. The task stays defined on iOS
 * anyway so a task registration left over from an older build resolves to
 * something harmless rather than an unknown-task error.
 */

import type { LocationObject } from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import { Platform } from 'react-native';

import { reportLocations } from '@/lib/location-report';

export const LOCATION_TASK = 'marshalls-foreman-location';

// Background location is a native-only capability.
if (Platform.OS !== 'web') {
  TaskManager.defineTask(LOCATION_TASK, async ({ data, error }) => {
    if (error || !data) {
      return;
    }

    const { locations } = data as { locations?: LocationObject[] };
    if (!locations || locations.length === 0) {
      return;
    }

    await reportLocations(locations);
  });
}
