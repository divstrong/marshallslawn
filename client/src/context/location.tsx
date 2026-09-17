/**
 * Drives foreman GPS tracking. When a foreman is signed in and has granted
 * location permission, position reporting starts automatically; any other role
 * (or signing out) stops it. A foreman can also pause sharing from Profile →
 * Location Sharing without revoking the OS permission; the pause persists
 * across launches until they turn it back on or sign out.
 *
 * How far the reporting reaches is a per-platform matter settled in
 * `lib/location.ts` — background on Android, foreground-only on iOS.
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { Platform } from 'react-native';

import { LocationDisclosure } from '@/components/location-disclosure';
import { LAST_LOCATION_SYNC_KEY, LOCATION_PAUSED_KEY } from '@/constants/config';
import { useAuth } from '@/context/auth';
import {
  canTrack,
  getTrackingPermission,
  isTrackingActive,
  requestTrackingPermission,
  startTracking,
  stopTracking,
  type TrackingPermission,
} from '@/lib/location';
import { getLastLocationSync, type LastLocationSync } from '@/lib/location-report';
import { getItem, removeItem, setItem } from '@/lib/storage';

interface LocationContextValue {
  /** True when the signed-in role is tracked (foreman). */
  tracksThisRole: boolean;
  /** Location runs on native devices only. */
  supported: boolean;
  permission: TrackingPermission;
  active: boolean;
  /** True when the employee turned sharing off in-app (permission may still be granted). */
  paused: boolean;
  /** The last batch this device delivered to dispatch, if any. */
  lastSync: LastLocationSync | null;
  /** Prompt for permission (if needed), clear any pause, and start tracking. */
  enable: () => Promise<void>;
  /** Stop tracking and remember the choice so it doesn't restart on next launch. */
  disable: () => Promise<void>;
}

async function isPaused(): Promise<boolean> {
  return (await getItem(LOCATION_PAUSED_KEY)) === '1';
}

const LocationContext = createContext<LocationContextValue | undefined>(undefined);

export function LocationProvider({ children }: { children: React.ReactNode }) {
  const { employee, status } = useAuth();
  const tracksThisRole =
    status === 'authenticated' &&
    (employee?.role === 'foreman' || employee?.role === 'spray_tech');
  const supported = Platform.OS !== 'web';

  const [permission, setPermission] = useState<TrackingPermission>('undetermined');
  const [active, setActive] = useState(false);
  const [paused, setPaused] = useState(false);
  const [lastSync, setLastSync] = useState<LastLocationSync | null>(null);
  const [disclosureVisible, setDisclosureVisible] = useState(false);
  // Session-only: a foreman who dismisses the disclosure isn't nagged again
  // until they ask for it from Profile → Location Sharing.
  const declinedRef = useRef(false);

  // Reports are delivered from outside React — a headless task on Android, a
  // watcher callback on iOS — so poll while a tracked role is signed in to
  // keep the Location Sharing card honest.
  useEffect(() => {
    if (!tracksThisRole) {
      setLastSync(null);
      return;
    }

    let cancelled = false;
    const read = async () => {
      const value = await getLastLocationSync();
      // Tracking can begin a moment after we ask for it — on Android the
      // start waits for the app to reach the foreground — so re-read it here
      // rather than trusting the value from when we first asked.
      const running = await isTrackingActive();
      if (!cancelled) {
        setLastSync(value);
        setActive(running);
      }
    };

    read();
    const timer = setInterval(read, 15_000);
    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [tracksThisRole]);

  // Start tracking for a signed-in foreman; stop it for anyone else.
  useEffect(() => {
    let cancelled = false;

    (async () => {
      if (!tracksThisRole) {
        await stopTracking();
        // The pause belongs to the person who set it; the next foreman to
        // sign in on this phone should get the default (tracking on) — and
        // should not see the previous foreman's last reported position.
        await Promise.all([removeItem(LOCATION_PAUSED_KEY), removeItem(LAST_LOCATION_SYNC_KEY)]);
        if (!cancelled) {
          setActive(false);
          setPaused(false);
          setDisclosureVisible(false);
        }
        return;
      }

      const [resolved, pausedByUser] = await Promise.all([getTrackingPermission(), isPaused()]);
      if (cancelled) return;
      setPermission(resolved);
      setPaused(pausedByUser);

      if (pausedByUser) {
        // Honour the in-app "off" even though the OS permission still allows it.
      } else if (canTrack(resolved)) {
        try {
          await startTracking();
        } catch {
          // e.g. running in Expo Go, which lacks background location.
        }
      } else if (resolved === 'undetermined' && !declinedRef.current) {
        // Foreman tracking is a job requirement, so ask on first sign-in — but
        // Google Play requires the disclosure to precede the OS prompt, so this
        // opens the disclosure rather than requesting permission directly.
        setDisclosureVisible(true);
      }

      if (!cancelled) {
        setActive(await isTrackingActive());
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [tracksThisRole]);

  /** Prompt the OS and start tracking. Only ever called after the disclosure. */
  const requestAndStart = useCallback(async () => {
    const result = await requestTrackingPermission();
    setPermission(result);
    // "While Using" is enough to run; the Settings card nudges toward "Always".
    if (canTrack(result)) {
      try {
        await startTracking();
      } catch {
        // Background location needs a development build, not Expo Go.
      }
    }
    setActive(await isTrackingActive());
  }, []);

  const acceptDisclosure = useCallback(async () => {
    setDisclosureVisible(false);
    await requestAndStart();
  }, [requestAndStart]);

  const declineDisclosure = useCallback(() => {
    declinedRef.current = true;
    setDisclosureVisible(false);
  }, []);

  const enable = useCallback(async () => {
    await removeItem(LOCATION_PAUSED_KEY);
    setPaused(false);
    // Re-show the disclosure whenever an OS prompt is still to come.
    if ((await getTrackingPermission()) === 'undetermined') {
      declinedRef.current = false;
      setDisclosureVisible(true);
      return;
    }
    await requestAndStart();
  }, [requestAndStart]);

  const disable = useCallback(async () => {
    // Persist first so a crash or kill mid-stop still leaves it off on relaunch.
    await setItem(LOCATION_PAUSED_KEY, '1');
    setPaused(true);
    await stopTracking();
    setActive(await isTrackingActive());
  }, []);

  const value = useMemo<LocationContextValue>(
    () => ({ tracksThisRole, supported, permission, active, paused, lastSync, enable, disable }),
    [tracksThisRole, supported, permission, active, paused, lastSync, enable, disable],
  );

  return (
    <LocationContext.Provider value={value}>
      {children}
      {supported ? (
        <LocationDisclosure
          visible={disclosureVisible}
          onAccept={acceptDisclosure}
          onDecline={declineDisclosure}
        />
      ) : null}
    </LocationContext.Provider>
  );
}

export function useLocationTracking(): LocationContextValue {
  const context = useContext(LocationContext);
  if (!context) {
    throw new Error('useLocationTracking must be used within a LocationProvider');
  }
  return context;
}
