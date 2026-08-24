/**
 * Crash + error reporting (Sentry).
 *
 * The crew runs this app on their own Android phones and tablets out in the
 * field. When something crashes there we get nothing back: Play Console's
 * Android vitals only covers builds installed *by Play*, on devices whose
 * owner opted into diagnostics sharing — a sideloaded EAS `preview` APK
 * reports literally nothing, and a bug we can't reproduce in the office is
 * otherwise invisible. This module closes that gap.
 *
 * It reports three things Play Console cannot:
 *   - native Android crashes (the `FATAL EXCEPTION` in logcat),
 *   - unhandled JavaScript errors, which are fatal in a release build,
 *   - a replay of the last seconds before the crash, fully masked.
 *
 * Everything here no-ops without EXPO_PUBLIC_SENTRY_DSN, so local dev and
 * Expo Go are unaffected and no reports are sent until the DSN is set.
 */

import * as Sentry from '@sentry/react-native';
import Constants, { ExecutionEnvironment } from 'expo-constants';
import * as Device from 'expo-device';

import { APP_VERSION } from '@/constants/config';
import type { Employee } from '@/lib/types';

/** Set in EAS build env / `.env`. Empty locally, which disables reporting. */
const DSN = process.env.EXPO_PUBLIC_SENTRY_DSN ?? '';

/**
 * Which build this is, so a crash from the crew's production app isn't mixed
 * in with one from an internal test build. EAS sets this per build profile.
 */
const ENVIRONMENT =
  process.env.EXPO_PUBLIC_ENV ?? (__DEV__ ? 'development' : 'production');

const isExpoGo = Constants.executionEnvironment === ExecutionEnvironment.StoreClient;

/**
 * Reporting is off in dev by default — a red box already tells us everything
 * and it keeps our error quota for real crashes. Set EXPO_PUBLIC_SENTRY_DEBUG=1
 * to verify the wiring locally before shipping a build.
 */
const forceInDev = process.env.EXPO_PUBLIC_SENTRY_DEBUG === '1';

export const monitoringEnabled = DSN.length > 0 && (!__DEV__ || forceInDev);

/**
 * Screen-transition breadcrumbs, so a report shows the path the crew member
 * took to the crash rather than just where they landed. Registered against
 * expo-router's navigation container in the root layout.
 */
export const navigationIntegration = Sentry.reactNavigationIntegration({
  enableTimeToInitialDisplay: false,
});

/**
 * Start reporting. Called once, at module scope in the root layout, so that
 * errors thrown while the tree is still mounting are captured too.
 */
export function initMonitoring(): void {
  if (!monitoringEnabled) {
    // Silence here would mean shipping a build to the crew that reports
    // nothing while looking fine — the exact situation this module exists to
    // end. Say so loudly in any build that was supposed to have it.
    if (!__DEV__ && DSN.length === 0) {
      console.warn(
        '[Marshalls Lawn] EXPO_PUBLIC_SENTRY_DSN is not set — this build reports no crashes.',
      );
    }
    return;
  }

  Sentry.init({
    dsn: DSN,
    environment: ENVIRONMENT,
    release: APP_VERSION,

    // This app carries employee names, addresses, and GPS traces. Never let
    // the SDK attach identifying data on its own — `setMonitoringUser` below
    // attaches the employee id and role deliberately, and nothing else.
    sendDefaultPii: false,

    // Native (Java/Kotlin) crash capture. This is what catches the class of
    // crash that kills the app outright with nothing printed in JS.
    enableNative: !isExpoGo,
    enableNativeCrashHandling: !isExpoGo,

    // "The app froze" reports from the field become real, diagnosable events.
    enableAppHangTracking: true,

    // Crash-free session rate per release — the headline number to watch
    // after shipping a build to the crew.
    enableAutoSessionTracking: true,

    // A user tapping through three screens before a crash gives us the repro
    // steps we couldn't get by asking.
    maxBreadcrumbs: 100,
    attachStacktrace: true,

    // Performance tracing is not what we're here for; sample it lightly so it
    // doesn't eat the quota that crash reports need.
    tracesSampleRate: 0.1,

    // No routine session recording, but record the seconds leading up to an
    // error — the closest thing to watching the crash happen on the crew's
    // own device. Text, images, and vectors are all masked (SDK defaults,
    // set explicitly here because this app is on screen in front of
    // customers' homes).
    replaysSessionSampleRate: 0,
    replaysOnErrorSampleRate: 1.0,

    integrations: [
      navigationIntegration,
      Sentry.mobileReplayIntegration({
        maskAllText: true,
        maskAllImages: true,
        maskAllVectors: true,
      }),
    ],
  });

  // The reason we are here: a bug that only shows up on the crew's own
  // hardware. Tagging every report with these makes "physical devices only"
  // or "only the Samsung tablets" a filter in the dashboard rather than a
  // hunch. `isDevice` is false on a simulator, and several code paths in
  // this app branch on exactly that.
  Sentry.setTag('isDevice', String(Device.isDevice));
  Sentry.setTag('deviceModel', Device.modelName ?? 'unknown');
  Sentry.setTag('osVersion', Device.osVersion ?? 'unknown');
  Sentry.setTag('expoGo', String(isExpoGo));
}

/**
 * Tag every subsequent report with who hit it. The office can then answer
 * "whose tablet is crashing?" — which is usually the fastest route to a
 * repro, since it's often one device or one role.
 *
 * Deliberately no name and no email: the employee id is enough to look the
 * person up in the back office, and keeps personal data out of Sentry.
 */
export function setMonitoringUser(employee: Employee): void {
  if (!monitoringEnabled) {
    return;
  }
  Sentry.setUser({ id: String(employee.id) });
  Sentry.setTag('role', employee.role);
}

/** Drop the identity on sign-out so a shared tablet doesn't mislabel reports. */
export function clearMonitoringUser(): void {
  if (!monitoringEnabled) {
    return;
  }
  Sentry.setUser(null);
}

/**
 * Record a step the user took. These ride along on the next crash report and
 * are what turn "it crashed" into "it crashed right after requesting a code".
 */
export function trace(message: string, data?: Record<string, unknown>): void {
  if (!monitoringEnabled) {
    return;
  }
  Sentry.addBreadcrumb({ category: 'app', level: 'info', message, data });
}

/**
 * Report an error we caught and handled. Use for failures that are survivable
 * but shouldn't be happening — unhandled ones arrive on their own.
 */
export function report(error: unknown, context?: Record<string, unknown>): void {
  if (!monitoringEnabled) {
    return;
  }
  Sentry.captureException(error, context ? { extra: context } : undefined);
}

/** Wraps the root component so React render errors reach Sentry. */
export const withMonitoring = Sentry.wrap;
