/**
 * Push notification setup.
 *
 * `expo-notifications` throws on import inside Expo Go (SDK 53+ removed
 * remote push from Expo Go), so it is NEVER imported statically — it is
 * lazily `require`d, and only in a real dev/standalone build. In Expo Go
 * or on web, every function here is a no-op.
 *
 * Registering a token also needs an EAS project id (run `eas init`).
 */

import Constants, { ExecutionEnvironment } from 'expo-constants';
import * as Device from 'expo-device';
import { Platform } from 'react-native';

import { Brand } from '@/constants/theme';

type NotificationsModule = typeof import('expo-notifications');

const isExpoGo = Constants.executionEnvironment === ExecutionEnvironment.StoreClient;
const pushSupported = Platform.OS !== 'web' && !isExpoGo;

let cached: NotificationsModule | null = null;

/** Lazily load expo-notifications — only safe outside Expo Go / web. */
function loadNotifications(): NotificationsModule | null {
  if (!pushSupported) {
    return null;
  }
  if (!cached) {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    cached = require('expo-notifications') as NotificationsModule;
    // Show notifications while the app is foregrounded.
    cached.setNotificationHandler({
      handleNotification: async () => ({
        shouldPlaySound: true,
        shouldSetBadge: false,
        shouldShowBanner: true,
        shouldShowList: true,
      }),
    });
  }
  return cached;
}

function getProjectId(): string | undefined {
  const fromConfig = Constants.expoConfig?.extra?.eas?.projectId;
  const fromEas = (Constants as { easConfig?: { projectId?: string } }).easConfig?.projectId;
  return fromConfig ?? fromEas;
}

/**
 * Request permission and return this device's Expo push token, or null
 * if push isn't available (Expo Go, web, simulator, denied, or no EAS
 * project).
 */
export async function registerForPushNotifications(): Promise<string | null> {
  const Notifications = loadNotifications();
  if (!Notifications || !Device.isDevice) {
    return null;
  }

  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('chat', {
      name: 'Chat messages',
      importance: Notifications.AndroidImportance.HIGH,
      lightColor: Brand[500],
      vibrationPattern: [0, 250, 250, 250],
    });
    // Job alerts (skipped / canceled jobs) — issue #14.
    await Notifications.setNotificationChannelAsync('alerts', {
      name: 'Job alerts',
      importance: Notifications.AndroidImportance.HIGH,
      lightColor: Brand[500],
      vibrationPattern: [0, 250, 250, 250],
    });
  }

  const existing = await Notifications.getPermissionsAsync();
  let granted = existing.granted;
  if (!granted && existing.canAskAgain) {
    granted = (await Notifications.requestPermissionsAsync()).granted;
  }
  if (!granted) {
    return null;
  }

  const projectId = getProjectId();
  if (!projectId) {
    // Push tokens need an EAS project — set one up with `eas init`.
    return null;
  }

  try {
    const token = await Notifications.getExpoPushTokenAsync({ projectId });
    return token.data;
  } catch {
    return null;
  }
}

/**
 * Subscribe to notification taps + foreground arrivals. Returns a
 * cleanup function; a no-op when push isn't available.
 */
export function addChatNotificationListeners(handlers: {
  onTap: () => void;
  onReceive: () => void;
}): () => void {
  const Notifications = loadNotifications();
  if (!Notifications) {
    return () => {};
  }

  const tap = Notifications.addNotificationResponseReceivedListener((response) => {
    if (response.notification.request.content.data?.type === 'chat') {
      handlers.onTap();
    }
  });
  const received = Notifications.addNotificationReceivedListener(() => {
    handlers.onReceive();
  });

  return () => {
    tap.remove();
    received.remove();
  };
}

/**
 * Subscribe to job-alert notification taps (skipped / canceled jobs — issue #14).
 * Calls `onTap` with the job id. Returns a cleanup function; a no-op when push
 * isn't available.
 */
export function addJobNotificationListeners(handlers: {
  onTap: (jobId: number | null) => void;
}): () => void {
  const Notifications = loadNotifications();
  if (!Notifications) {
    return () => {};
  }

  const tap = Notifications.addNotificationResponseReceivedListener((response) => {
    const data = response.notification.request.content.data;
    if (data?.type === 'job') {
      const jobId = typeof data.job_id === 'number' ? data.job_id : Number(data.job_id) || null;
      handlers.onTap(jobId);
    }
  });

  return () => {
    tap.remove();
  };
}
