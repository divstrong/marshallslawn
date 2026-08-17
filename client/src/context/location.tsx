/**
 * Drives foreman GPS tracking. When a foreman is signed in and has
 * granted location permission, background updates run automatically;
 * any other role (or signing out) stops tracking.
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { Platform } from 'react-native';

import { LocationDisclosure } from '@/components/location-disclosure';
import { useAuth } from '@/context/auth';
import {
  getTrackingPermission,
  isTrackingActive,
  requestTrackingPermission,
  startTracking,
  stopTracking,
  type TrackingPermission,
} from '@/lib/location';
import { getLastLocationSync, type LastLocationSync } from '@/lib/location-task';

interface LocationContextValue {
  /** True when the signed-in role is tracked (foreman). */
  tracksThisRole: boolean;
  /** Location runs on native devices only. */
  supported: boolean;
  permission: TrackingPermission;
  active: boolean;
  /** The last batch this device delivered to dispatch, if any. */
  lastSync: LastLocationSync | null;
  /** Prompt for permission and start tracking. */
  enable: () => Promise<void>;
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
  const [lastSync, setLastSync] = useState<LastLocationSync | null>(null);
  const [disclosureVisible, setDisclosureVisible] = useState(false);
  // Session-only: a foreman who dismisses the disclosure isn't nagged again
  // until they ask for it from Profile → Location Sharing.
  const declinedRef = useRef(false);

  // The background task writes to storage from outside React, so poll while a
  // tracked role is signed in to keep the Location Sharing card honest.
  useEffect(() => {
    if (!tracksThisRole) {
      setLastSync(null);
      return;
    }

    let cancelled = false;
    const read = async () => {
      const value = await getLastLocationSync();
      if (!cancelled) {
        setLastSync(value);
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
        if (!cancelled) {
          setActive(false);
          setDisclosureVisible(false);
        }
        return;
      }

      const resolved = await getTrackingPermission();
      if (cancelled) return;
      setPermission(resolved);

      if (resolved === 'granted') {
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
    if (result === 'granted') {
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
    // Re-show the disclosure whenever an OS prompt is still to come.
    if ((await getTrackingPermission()) === 'undetermined') {
      declinedRef.current = false;
      setDisclosureVisible(true);
      return;
    }
    await requestAndStart();
  }, [requestAndStart]);

  const value = useMemo<LocationContextValue>(
    () => ({ tracksThisRole, supported, permission, active, lastSync, enable }),
    [tracksThisRole, supported, permission, active, lastSync, enable],
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
