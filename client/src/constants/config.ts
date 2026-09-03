/**
 * Base URL for the Laravel API.
 *
 * Defaults to the live production API, so release builds and on-device
 * testing work out of the box. For local development against a local
 * server, override it with EXPO_PUBLIC_API_URL (e.g. a `.env` file at
 * the client root) and restart the bundler:
 *
 *   # iOS simulator / web
 *   EXPO_PUBLIC_API_URL=http://localhost:8000/api
 *   # Android emulator (the host machine is reached via 10.0.2.2)
 *   EXPO_PUBLIC_API_URL=http://10.0.2.2:8000/api
 *   # physical device on the same network as the dev machine
 *   EXPO_PUBLIC_API_URL=http://192.168.1.20:8000/api
 *
 * EXPO_PUBLIC_* values are inlined at bundle time — restart Expo
 * (`expo start --clear`) after changing them.
 */
const PRODUCTION_API_URL = 'https://app.marshallslawninc.com/api';

export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? PRODUCTION_API_URL;

if (__DEV__) {
  // Surfaces the active API URL in the Metro console on every app start.
  console.log('[Marshalls Lawn] API base URL →', API_BASE_URL);
}

/** Storage key for the persisted auth token. */
export const TOKEN_STORAGE_KEY = 'marshalls.auth.token';

/** Storage key for the registered Expo push token. */
export const PUSH_TOKEN_STORAGE_KEY = 'marshalls.push.token';

/** Storage key for the selected app language. */
export const LANGUAGE_STORAGE_KEY = 'marshalls.language';

/**
 * Storage key for when the background location task last reached dispatch.
 * Written from outside React (the task runs headless), read back by the
 * Location Sharing card so crews — and App Review — can see that background
 * reporting is genuinely running.
 */
export const LAST_LOCATION_SYNC_KEY = 'marshalls.location.lastSync';

/**
 * Storage key set while a tracked employee has turned location sharing off
 * from inside the app. Without it, tracking would silently restart on the
 * next launch because the OS permission is still "Always" — App Review and
 * Play policy both expect an in-app way to stop that doesn't require
 * revoking the device permission. Cleared on sign-out.
 */
export const LOCATION_PAUSED_KEY = 'marshalls.location.paused';

/**
 * Storage key set once this install has asked the OS for "Always" location.
 * Neither platform will show that prompt twice (iOS: once per install;
 * Android 11+: it's a settings page), and expo-location only remembers that
 * it asked for the lifetime of the process — so this is how a "While Using"
 * grant is told apart from "not asked yet" across launches.
 */
export const LOCATION_ALWAYS_ASKED_KEY = 'marshalls.location.alwaysAsked';

export const APP_VERSION = '1.0.0-beta';
