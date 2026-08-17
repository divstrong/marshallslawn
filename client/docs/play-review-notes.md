# Google Play review notes — background location

Companion to `app-review-notes.md` (which covers Apple). Google's process is
different: it is a **form plus a mandatory video**, not a written justification.

---

## The two errors in the console

They are separate forms and only one of them needs a video.

### 1. "Foreground Service permissions" — easy, no video

Play Console → **App content** → **Foreground service permissions**. Declare:

| Field | Answer |
| --- | --- |
| Permission | `FOREGROUND_SERVICE_LOCATION` |
| Purpose | Ongoing crew location sharing with the dispatch office during a work shift |
| Description | While a foreman or spray tech is signed in, the app runs a location-type foreground service that reports the crew's position to the dispatch office roughly every 60 seconds. A persistent notification ("Sharing your location with dispatch while on the clock") is visible the entire time the service runs. |

Started by `startTracking()` in `src/lib/location.ts` via the `foregroundService`
option on `startLocationUpdatesAsync`.

### 2. "Sensitive app permissions → Location" — needs the video

`ACCESS_BACKGROUND_LOCATION` is declared, so the video is mandatory and there is
no exemption. See below.

---

## Declaration form answers

**What is the main purpose of your app?** — Enterprise / business tool (crew
dispatch and job management for a landscaping company). Not consumer-facing;
distributed to employees.

**Why does your app need background location?**

> Marshall's Lawn is the field app for a landscaping company's crews. The office
> runs a live Dispatch map showing where every crew currently is and assigns the
> next job to the nearest crew. This requires the crew's phone to report position
> while the app is backgrounded and the screen is locked — a foreman is driving a
> truck or running a mower, not holding the phone with the app open. Position is
> reported roughly every 60 seconds or every 40 metres and stored as a breadcrumb
> trail. The Dispatch board marks a crew "live" only if its last report arrived
> within 15 minutes, so without background access a crew goes stale 15 minutes
> after the phone locks and the board stops being usable for dispatching.

**Which users are affected?** Only employees with the **Foreman** or **Spray
Tech** role, and only while signed in. Office staff and all other roles never
report location — see `tracksThisRole` in `src/context/location.tsx`.

**Why can't a foreground-only alternative work?** Covered below.

---

## Prominent disclosure

Google requires an in-app **prominent disclosure** shown *before* the runtime
permission prompt, and **the video must show it**. A video without it is
rejected even if everything else is right.

This is implemented in `src/components/location-disclosure.tsx` and shown by
`LocationProvider` (`src/context/location.tsx`). The provider no longer calls
`requestTrackingPermission()` directly — whenever an OS prompt is still to come,
it opens the disclosure first, and only an explicit tap on **Continue** triggers
the permission request. The backdrop is inert, so a dismissal can never be
mistaken for consent. Declining hides it for the session; the foreman can bring
it back from **Profile → Location Sharing → Enable location sharing**.

The disclosure text lives in `src/lib/translations.ts` under `location.disclose*`
(English and Spanish) and reads:

> Marshall's Lawn collects location data to show your crew's position to the
> dispatch office and send the next job to the nearest crew, even when the app is
> closed or not in use.

If you film in Spanish, note that Google expects the video's language to match
what the reviewer can follow — record in English unless you add a translation.

---

## Video script

Google's stated rules: *"Aim for a video duration of 30 seconds or less"* — a
guideline, not a hard limit — hosted as a **YouTube link** (preferred) or a
Google Drive link to an MP4. It must show the prominent disclosure, the runtime
prompt with consent, and the declared feature working from the background.

**On length:** tracking is floored at a 60-second interval, so two different
"Last sent to dispatch" values cannot both appear within 30 seconds. Either run
slightly long (allowed — "aim for" is not a cap) or cut between the two readings.
Do not shorten the interval just for the video; the footage should reflect
shipped behaviour.

Record a release build — either `eas build -p android --profile preview`, or
locally with `npx expo prebuild -p android && (cd android && ./gradlew
assembleRelease)`. Background location does **not** work in Expo Go.

An **emulator recording is acceptable** — Google's policy does not require a
physical device, and the Android emulator can mock GPS movement via Extended
controls → Location (GPX/KML playback), which is what makes the timestamp
advance. Use a **Google Play / Google APIs** system image: `expo-location` relies
on the fused location provider from Google Play services, which AOSP images lack.
What Google *does* reject is iOS footage — the video must show Android.

| Time | Shot |
| --- | --- |
| 0:00–0:04 | App open, sign in as a Foreman (the demo account in `app-review-notes.md`) |
| 0:04–0:11 | **Prominent disclosure screen**, on screen long enough to read, then tap accept |
| 0:11–0:16 | Android runtime prompt → choose **Allow all the time** (the Android 11+ flow sends you to system settings; include that) |
| 0:16–0:22 | Profile → Location Sharing card showing the first "Last sent to dispatch" line |
| 0:22–0:26 | Press home / lock the screen — show the **persistent foreground-service notification** in the shade |
| 0:26–0:30 | Reopen the app; the "Last sent to dispatch" timestamp has advanced while backgrounded |

Playing the foreman yourself is fine — Google wants a demonstration of the real
feature, and a staged walkthrough by a developer is the normal way to film it.
What it cannot do is depict behaviour the app does not actually have.

To capture the last two rows convincingly, walk ≥40 m or drive a short distance
so a real update fires. `adb shell screenrecord` will not capture the lock
screen; use the device's built-in screen recorder instead.

---

## Hosting and submitting the video

A YouTube link is Google's stated preference, but **a Google Drive link to an MP4
is explicitly supported** — no YouTube channel required.

1. Upload the MP4 to Drive.
2. **Share → General access → "Anyone with the link" → Viewer.**
3. **Check the link in an incognito window, signed out.** If it asks to sign in
   or request access, the reviewer hits the same wall.
4. Paste it into **Play Console → App content → Sensitive app permissions →
   Location permissions**.

Google's guidance is explicit that a request is not approved if the video is
missing the required content *or the link can't be accessed*. An unshared Drive
link is therefore a silent rejection that costs a full review cycle.

Do not set an expiry, do not restrict to a domain, and keep the file in place
after approval — resubmissions and future releases reuse the same link. Record
the URL here once it exists so the next submission doesn't have to re-shoot:

    Video URL: (fill in)

## Can the video be avoided? (the honest answer)

Only by dropping `ACCESS_BACKGROUND_LOCATION`, which means changing the feature.

Google does allow a foreground-service-only pattern: a location-type FGS started
while the app is visible keeps receiving location after the app is backgrounded,
*without* background permission. On paper that fits a shift model — the foreman
opens the app and starts a shift.

Two reasons it is not a clean escape here:

1. Google's own carve-out says the FGS must be "a continuation of an in-app,
   user-initiated action" and "terminated immediately after" it completes, and
   that FGS use *equivalent to* background access still needs approval. All-day
   continuous crew tracking is very likely to be judged equivalent.
2. `expo-location` requires background permission for `startLocationUpdatesAsync`
   on Android ("you must request both foreground and background permissions"), so
   this would mean leaving `expo-location`'s background API behind, not a config
   flag.

It would also lose auto-resume after a reboot, since a foreground service cannot
be restarted from the background.

Given the app genuinely needs continuous tracking, filling in the form and
filming the video is the shorter path.

---

## Unrelated cleanup worth doing first

`android.permission.RECORD_AUDIO` is declared in `app.json:38` but nothing in
the codebase records audio. Removing it drops a microphone permission from the
store listing and shrinks the sensitive-permission surface being reviewed.
