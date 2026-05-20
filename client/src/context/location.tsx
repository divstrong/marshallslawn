/**
 * Drives foreman GPS tracking. When a foreman is signed in and has
 * granted location permission, background updates run automatically;
 * any other role (or signing out) stops tracking.
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';

import { useAuth } from '@/context/auth';
import {
  getTrackingPermission,
  isTrackingActive,
  requestTrackingPermission,
  startTracking,
  stopTracking,
  type TrackingPermission,
} from '@/lib/location';

interface LocationContextValue {
  /** True when the signed-in role is tracked (foreman). */
  tracksThisRole: boolean;
  /** Location runs on native devices only. */
  supported: boolean;
  permission: TrackingPermission;
  active: boolean;
  /** Prompt for permission and start tracking. */
  enable: () => Promise<void>;
}

const LocationContext = createContext<LocationContextValue | undefined>(undefined);

export function LocationProvider({ children }: { children: React.ReactNode }) {
  const { employee, status } = useAuth();
  const tracksThisRole = status === 'authenticated' && employee?.role === 'foreman';
  const supported = Platform.OS !== 'web';

  const [permission, setPermission] = useState<TrackingPermission>('undetermined');
  const [active, setActive] = useState(false);

  // Start tracking for a signed-in foreman; stop it for anyone else.
  useEffect(() => {
    let cancelled = false;

    (async () => {
      if (!tracksThisRole) {
        await stopTracking();
        if (!cancelled) {
          setActive(false);
        }
        return;
      }

      let resolved = await getTrackingPermission();
      // Foreman tracking is a job requirement — prompt on first sign-in.
      if (resolved === 'undetermined') {
        resolved = await requestTrackingPermission();
      }
      if (cancelled) return;
      setPermission(resolved);

      if (resolved === 'granted') {
        try {
          await startTracking();
        } catch {
          // e.g. running in Expo Go, which lacks background location.
        }
      }
      if (!cancelled) {
        setActive(await isTrackingActive());
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [tracksThisRole]);

  const enable = useCallback(async () => {
    const result = await requestTrackingPermission();
    setPermission(result);
    if (result === 'granted') {
      try {
        await startTracking();
      } catch {
        // Background location needs a development build, not Expo Go.
      }
      setActive(await isTrackingActive());
    }
  }, []);

  const value = useMemo<LocationContextValue>(
    () => ({ tracksThisRole, supported, permission, active, enable }),
    [tracksThisRole, supported, permission, active, enable],
  );

  return <LocationContext.Provider value={value}>{children}</LocationContext.Provider>;
}

export function useLocationTracking(): LocationContextValue {
  const context = useContext(LocationContext);
  if (!context) {
    throw new Error('useLocationTracking must be used within a LocationProvider');
  }
  return context;
}
