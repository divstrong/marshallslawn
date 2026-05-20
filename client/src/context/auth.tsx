/**
 * Session state for the app. Holds the bearer token + authenticated
 * employee, persists the token across launches, and exposes sign-in
 * (email/password and dev role shortcut) and sign-out.
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { TOKEN_STORAGE_KEY } from '@/constants/config';
import { api, setAuthToken } from '@/lib/api';
import { getItem, removeItem, setItem } from '@/lib/storage';
import type { Employee, Role } from '@/lib/types';

type Status = 'loading' | 'authenticated' | 'unauthenticated';

interface AuthContextValue {
  status: Status;
  employee: Employee | null;
  signIn: (email: string, password: string) => Promise<void>;
  signInWithRole: (role: Role) => Promise<void>;
  signOut: () => Promise<void>;
  refresh: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [status, setStatus] = useState<Status>('loading');
  const [employee, setEmployee] = useState<Employee | null>(null);

  const applySession = useCallback(async (token: string, nextEmployee: Employee) => {
    setAuthToken(token);
    await setItem(TOKEN_STORAGE_KEY, token);
    setEmployee(nextEmployee);
    setStatus('authenticated');
  }, []);

  const clearSession = useCallback(async () => {
    setAuthToken(null);
    await removeItem(TOKEN_STORAGE_KEY);
    setEmployee(null);
    setStatus('unauthenticated');
  }, []);

  // Restore a persisted token on cold start.
  useEffect(() => {
    let active = true;

    (async () => {
      const token = await getItem(TOKEN_STORAGE_KEY);
      if (!token) {
        if (active) {
          setStatus('unauthenticated');
        }
        return;
      }

      setAuthToken(token);
      try {
        const me = await api.me();
        if (active) {
          setEmployee(me);
          setStatus('authenticated');
        }
      } catch {
        await removeItem(TOKEN_STORAGE_KEY);
        setAuthToken(null);
        if (active) {
          setStatus('unauthenticated');
        }
      }
    })();

    return () => {
      active = false;
    };
  }, []);

  const signIn = useCallback(
    async (email: string, password: string) => {
      const { token, employee: nextEmployee } = await api.login(email, password);
      await applySession(token, nextEmployee);
    },
    [applySession],
  );

  const signInWithRole = useCallback(
    async (role: Role) => {
      const { token, employee: nextEmployee } = await api.devLogin(role);
      await applySession(token, nextEmployee);
    },
    [applySession],
  );

  const signOut = useCallback(async () => {
    try {
      await api.logout();
    } catch {
      // Even if the server call fails, drop the local session.
    }
    await clearSession();
  }, [clearSession]);

  const refresh = useCallback(async () => {
    try {
      setEmployee(await api.me());
    } catch {
      // Keep the existing employee on a transient failure.
    }
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({ status, employee, signIn, signInWithRole, signOut, refresh }),
    [status, employee, signIn, signInWithRole, signOut, refresh],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
