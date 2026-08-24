# Crash reporting

Sentry is wired into the app so crashes on the crew's own phones and tablets
reach us with a stack trace, instead of arriving as "it crashed when I signed
in" three days later.

**It stays completely off until `EXPO_PUBLIC_SENTRY_DSN` is set at build
time.** Nothing below happens by default.

---

## Why not Play Console

Android vitals only reports crashes from builds **installed by Google Play**,
on devices whose owner opted into usage-and-diagnostics sharing, above a
minimum volume threshold, with roughly a day of lag. The `preview` profile in
`eas.json` is `distribution: internal` — a sideloaded APK, which reports
nothing at all, ever. An empty Play Console is not evidence that nothing
crashed.

Do both: set Sentry up as below *and* push builds through Play's internal
testing track so vitals starts working too. Sentry is the one that gives a
readable JavaScript stack trace.

---

## One-time setup

### 1. Create the Sentry project

At sentry.io, create a **React Native** project. Note three values:

| Value | Where it is | Secret? |
| --- | --- | --- |
| DSN | Project Settings → Client Keys (DSN) | No — it ships inside the app |
| Org slug | The URL: `sentry.io/organizations/<org>/` | No |
| Project slug | Project Settings → Name | No |

Then create an **auth token** at Settings → Auth Tokens with the
`project:releases` and `org:read` scopes. **This one is secret** — it is what
lets a build upload source maps.

### 2. Give EAS the values

**Already done.** The DSN, org, and project are not sensitive — the DSN ships
inside the app either way — so they live in `eas.json`, in each build
profile's `env` block:

| Variable | Value |
| --- | --- |
| `EXPO_PUBLIC_SENTRY_DSN` | the project's DSN |
| `SENTRY_ORG` | `divstrong-productions-llc` |
| `SENTRY_PROJECT` | `android` |

The auth token must **not** go in the repo. Store it as an EAS secret:

```bash
npx eas secret:create --scope project --name SENTRY_AUTH_TOKEN --value <token> --type string
```

For local release builds, put it in `client/.env.local` instead (already
gitignored):

```
SENTRY_AUTH_TOKEN=<token>
```

> Renaming the Sentry project changes its **slug**, and `SENTRY_PROJECT` in
> `eas.json` has to be updated to match — otherwise source-map upload fails
> at build time and release stack traces go back to being unreadable.

### 3. Build

```bash
npx eas build --platform android --profile preview
```

Source maps upload automatically during the build — that is what turns a
minified one-line stack trace into real file names and line numbers.

---

## Verify it actually works

Do this once per configured profile. A build that reports nothing looks
identical to a build with no crashes.

1. Install the build on a physical Android device.
2. Sign in, then force an error. The quickest honest check is to add a
   temporary throw behind a button, build, tap it, and remove it after.
3. The event should appear in Sentry within about a minute, tagged
   `isDevice: true` with the device model.

If nothing arrives, check the device log for the warning the app prints when
the DSN is missing:

```bash
adb logcat | grep "Marshalls Lawn"
```

To test locally without a release build, set both `EXPO_PUBLIC_SENTRY_DSN`
and `EXPO_PUBLIC_SENTRY_DEBUG=1` in `client/.env` — reporting is off in
development otherwise, so a red box doesn't burn the error quota.

---

## What a report contains

- **Native Android crashes** — the `FATAL EXCEPTION` that kills the app with
  nothing printed in JavaScript. This is the class of crash that leaves no
  trace anywhere today.
- **Unhandled JavaScript errors**, which are fatal in a release build.
- **App hangs / ANRs.**
- **Breadcrumbs** — the screens the crew member moved through, plus explicit
  markers at `auth.request_code`, `auth.verify_code`, and `api.unreachable`
  (a failed or timed-out API call, with the host and whether it timed out).
- **A masked replay** of the seconds before the error. Text, images, and
  vector graphics are all masked, so it shows the shape of what happened, not
  readable customer data.
- **Tags**: `isDevice` (false on a simulator — several code paths in this app
  branch on exactly that), `deviceModel`, `osVersion`, `role`, `expoGo`, and
  the build `environment`.
- **User**: the employee id only. No name, no email — look the id up in the
  back office. `sendDefaultPii` is off deliberately, because this app carries
  employee addresses and GPS traces.

## The support code

When a JavaScript error is caught by the error boundary, the crew member sees
a recoverable screen with an eight-character **support code** instead of the
app vanishing. That code is the first part of the Sentry event id — paste it
into Sentry's search to jump straight to their report.

---

## Still no report?

If a crash happens so early that the SDK hasn't started, or native reporting
is somehow disabled, get the log off the device directly:

```bash
adb logcat -b crash            # just the crash buffer
adb bugreport crash.zip        # everything, if the client can run it
```
