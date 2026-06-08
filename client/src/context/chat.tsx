/**
 * Foreman chat state shared across the app: the unread count for the
 * Chat tab badge, plus push-notification registration and tap handling.
 */

import { router } from 'expo-router';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { Platform } from 'react-native';

import { PUSH_TOKEN_STORAGE_KEY } from '@/constants/config';
import { useAuth } from '@/context/auth';
import { api } from '@/lib/api';
import { addChatNotificationListeners, registerForPushNotifications } from '@/lib/notifications';
import { setItem } from '@/lib/storage';

interface ChatContextValue {
  unread: number;
  refresh: () => Promise<void>;
}

const ChatContext = createContext<ChatContextValue | undefined>(undefined);

const POLL_INTERVAL_MS = 25_000;

export function ChatProvider({ children }: { children: React.ReactNode }) {
  const { employee, status } = useAuth();
  const hasChat =
    status === 'authenticated' &&
    (employee?.role === 'foreman' || employee?.role === 'spray_tech');

  const [unread, setUnread] = useState(0);

  const refresh = useCallback(async () => {
    if (!hasChat) {
      setUnread(0);
      return;
    }
    try {
      const { count } = await api.chatUnread();
      setUnread(count);
    } catch {
      // Leave the previous count on a transient failure.
    }
  }, [hasChat]);

  const refreshRef = useRef(refresh);
  refreshRef.current = refresh;

  // Poll the unread count for the tab badge.
  useEffect(() => {
    if (!hasChat) {
      setUnread(0);
      return;
    }
    refresh();
    const timer = setInterval(refresh, POLL_INTERVAL_MS);
    return () => clearInterval(timer);
  }, [hasChat, refresh]);

  // Register this device for push notifications once a foreman signs in.
  useEffect(() => {
    if (!hasChat) {
      return;
    }
    let active = true;
    (async () => {
      const token = await registerForPushNotifications();
      if (!active || !token) {
        return;
      }
      try {
        await api.registerPushToken(token, Platform.OS);
        await setItem(PUSH_TOKEN_STORAGE_KEY, token);
      } catch {
        // Push just won't be delivered until the next successful register.
      }
    })();
    return () => {
      active = false;
    };
  }, [hasChat]);

  // Tapping a chat notification opens the Chat tab; an in-app arrival
  // refreshes the badge. No-op in Expo Go / web.
  useEffect(() => {
    return addChatNotificationListeners({
      onTap: () => router.navigate('/chat'),
      onReceive: () => refreshRef.current(),
    });
  }, []);

  const value = useMemo<ChatContextValue>(() => ({ unread, refresh }), [unread, refresh]);

  return <ChatContext.Provider value={value}>{children}</ChatContext.Provider>;
}

export function useChat(): ChatContextValue {
  const context = useContext(ChatContext);
  if (!context) {
    throw new Error('useChat must be used within a ChatProvider');
  }
  return context;
}
