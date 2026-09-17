const { withInfoPlist } = require('expo/config-plugins');

/**
 * Pins `UIBackgroundModes` to exactly the modes the app actually uses — which,
 * as of build 1.0 (23), is none at all.
 *
 * Several Expo plugins append to this key on their own, and one of them does
 * it unconditionally: `expo-task-manager` adds `fetch` merely by being
 * installed, even though this app never registers a background fetch task —
 * TaskManager is here only to receive background location updates on Android.
 *
 * App Review rejected 1.0 (22) under guideline 2.5.4: the `location` mode was
 * declared for employee tracking, which Apple does not accept as a reason for
 * persistent location on the public App Store. iOS now reports position only
 * while the app is on screen (see `src/lib/location.ts`), so the key is
 * removed entirely rather than left as an empty array — an empty
 * `UIBackgroundModes` still reads as a declaration.
 *
 * This plugin must run last (list it last in `plugins`) so it replaces
 * whatever the earlier plugins accumulated.
 *
 * @param {import('expo/config-plugins').ExpoConfig} config
 * @param {{ modes?: string[] }} props
 */
const withBackgroundModes = (config, { modes = [] } = {}) =>
  withInfoPlist(config, (cfg) => {
    if (modes.length === 0) {
      delete cfg.modResults.UIBackgroundModes;
    } else {
      cfg.modResults.UIBackgroundModes = modes;
    }

    return cfg;
  });

module.exports = withBackgroundModes;
