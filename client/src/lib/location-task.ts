/**
 * Background location task. Defined at module scope so it is registered
 * whenever the JS bundle loads — including when the OS wakes the app to
 * deliver a location update. Import this once from the root layout.
 */

import type { LocationObject } from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import { Platform } from 'react-native';

import { API_BASE_URL, LAST_LOCATION_SYNC_KEY, TOKEN_STORAGE_KEY } from '@/constants/config';
import { getItem, setItem } from '@/lib/storage';

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
      const response = await fetch(`${API_BASE_URL}/locations`, {
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

      if (response.ok) {
        // Recorded so the Location Sharing card can show that background
        // reporting is actually reaching dispatch, not just permitted.
        const newest = locations[locations.length - 1];
        await setItem(
          LAST_LOCATION_SYNC_KEY,
          JSON.stringify({
            at: new Date(newest.timestamp).toISOString(),
            latitude: newest.coords.latitude,
            longitude: newest.coords.longitude,
            points: locations.length,
          }),
        );
      }
    } catch {
      // Drop this batch — the OS will deliver more.
    }
  });
}

export interface LastLocationSync {
  at: string;
  latitude: number;
  longitude: number;
  points: number;
}

/** The most recent batch this device delivered to dispatch, if any. */
export async function getLastLocationSync(): Promise<LastLocationSync | null> {
  const raw = await getItem(LAST_LOCATION_SYNC_KEY);
  if (!raw) {
    return null;
  }
  try {
    return JSON.parse(raw) as LastLocationSync;
  } catch {
    return null;
  }
}
