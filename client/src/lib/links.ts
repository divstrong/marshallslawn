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

/** Open turn-by-turn directions to a coordinate in the platform maps app. */
export function openDirections(lat: number, lng: number): void {
  const dest = `${lat},${lng}`;
  const url =
    Platform.select({
      ios: `maps://?daddr=${dest}`,
      android: `google.navigation:q=${dest}`,
      default: `https://www.google.com/maps/dir/?api=1&destination=${dest}`,
    }) ?? `https://www.google.com/maps/dir/?api=1&destination=${dest}`;
  Linking.openURL(url).catch(() => {
    // Fall back to the universal web URL if the native scheme isn't handled.
    Linking.openURL(`https://www.google.com/maps/dir/?api=1&destination=${dest}`).catch(() => {});
  });
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
