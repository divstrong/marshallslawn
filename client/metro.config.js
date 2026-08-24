// Learn more https://docs.expo.io/guides/customizing-metro
//
// `getSentryExpoConfig` is Expo's default Metro config plus the Debug ID
// injection Sentry needs to match a minified release bundle back to source.
// Without it, crash reports from a release build arrive as unreadable
// one-line stack traces.
const { getSentryExpoConfig } = require('@sentry/react-native/metro');

const config = getSentryExpoConfig(__dirname);

module.exports = config;
