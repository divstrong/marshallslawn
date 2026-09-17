/**
 * Delivery of position reports to dispatch, shared by both paths that produce
 * them: the Android background task in `location-task.ts` (headless, runs
 * outside React) and the iOS foreground watcher in `location.ts`.
 */

import type { LocationObject } from 'expo-location';

import { API_BASE_URL, LAST_LOCATION_SYNC_KEY, TOKEN_STORAGE_KEY } from '@/constants/config';
import { getItem, setItem } from '@/lib/storage';

export interface LastLocationSync {
  at: string;
  latitude: number;
  longitude: number;
  points: number;
}

/**
 * POST a batch of positions to dispatch. Never throws — one caller is a
 * headless background task where an unhandled rejection is a crash, and a
 * dropped batch costs nothing: another is a minute behind it.
 */
export async function reportLocations(locations: LocationObject[]): Promise<void> {
  if (locations.length === 0) {
    return;
  }

  // May run outside React, so read the token straight from storage.
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
      // Recorded so the Location Sharing card can show that reporting is
      // actually reaching dispatch, not just permitted.
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
    // Drop this batch — another is coming.
  }
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
