import { Platform } from 'react-native';

/**
 * Base URL for the Laravel API.
 *
 * Override per environment with `EXPO_PUBLIC_API_URL` (e.g. in a `.env`
 * file or the shell). The defaults assume `php artisan serve` on port
 * 8000 of the development machine:
 *   - Android emulator reaches the host via 10.0.2.2
 *   - iOS simulator / web reach it via localhost
 *
 * On a physical device, set EXPO_PUBLIC_API_URL to the host's LAN IP,
 * e.g. EXPO_PUBLIC_API_URL=http://192.168.1.20:8000/api
 */
const DEFAULT_API_URL = Platform.select({
  android: 'http://10.0.2.2:8000/api',
  default: 'http://localhost:8000/api',
});

export const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? DEFAULT_API_URL;

/** Storage key for the persisted auth token. */
export const TOKEN_STORAGE_KEY = 'marshalls.auth.token';

export const APP_VERSION = '1.0.0-beta';
