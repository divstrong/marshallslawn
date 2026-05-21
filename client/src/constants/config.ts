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

export const APP_VERSION = '1.0.0-beta';
