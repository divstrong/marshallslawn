/** Helpers for opening native maps and phone links. */

import { Linking, Platform } from 'react-native';

/** Open the platform maps app at a free-text address/query. */
export function openMaps(query: string): void {
  const q = encodeURIComponent(query);
  const url =
    Platform.select({
      ios: `maps:0,0?q=${q}`,
      android: `geo:0,0?q=${q}`,
      default: `https://maps.google.com/?q=${q}`,
    }) ?? `https://maps.google.com/?q=${q}`;
  Linking.openURL(url).catch(() => {});
}

/** Start a phone call. */
export function openPhone(phone: string): void {
  const clean = phone.replace(/[^0-9+]/g, '');
  Linking.openURL(`tel:${clean}`).catch(() => {});
}

/** Open a URL in the browser. */
export function openUrl(url: string): void {
  Linking.openURL(url).catch(() => {});
}
