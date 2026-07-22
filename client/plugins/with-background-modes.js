const { withInfoPlist } = require('expo/config-plugins');

/**
 * Pins `UIBackgroundModes` to exactly the modes the app actually uses.
 *
 * Several Expo plugins append to this key on their own, and one of them does
 * it unconditionally: `expo-task-manager` adds `fetch` merely by being
 * installed, even though this app never registers a background fetch task —
 * TaskManager is here only to receive background *location* updates.
 *
 * An unused background mode is precisely what App Store guideline 2.5.4
 * rejects, so this plugin runs last (list it last in `plugins`) and replaces
 * whatever the earlier plugins accumulated.
 *
 * @param {import('expo/config-plugins').ExpoConfig} config
 * @param {{ modes?: string[] }} props
 */
const withBackgroundModes = (config, { modes = [] } = {}) =>
  withInfoPlist(config, (cfg) => {
    cfg.modResults.UIBackgroundModes = modes;

    return cfg;
  });

module.exports = withBackgroundModes;
