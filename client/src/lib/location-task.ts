/**
 * Background location task. Defined at module scope so it is registered
 * whenever the JS bundle loads — including when the OS wakes the app to
 * deliver a location update. Import this once from the root layout.
 */

import type { LocationObject } from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import { Platform } from 'react-native';

import { API_BASE_URL, TOKEN_STORAGE_KEY } from '@/constants/config';
import { getItem } from '@/lib/storage';

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

    // The task runs outside React, so read the token straight from storage.
    const token = await getItem(TOKEN_STORAGE_KEY);
    if (!token) {
      return;
    }

    try {
      await fetch(`${API_BASE_URL}/locations`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          locations: locations.map((point) => ({
            latitude: point.coords.latitude,
            longitude: point.coords.longitude,
            accuracy: point.coords.accuracy,
            heading: point.coords.heading,
            speed: point.coords.speed,
            recorded_at: new Date(point.timestamp).toISOString(),
          })),
        }),
      });
    } catch {
      // Drop this batch — the OS will deliver more.
    }
  });
}
