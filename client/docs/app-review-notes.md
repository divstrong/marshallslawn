# App Store review notes

Paste the relevant sections into **App Store Connect → App Review Information → Notes**
on every submission. Reviewers do not see this repo.

---

## Demo account

```
Email: test@apple.com
Code:  123456
```

There is no password. Field-app sign-in is passwordless: enter the email, tap
**Send code**, then enter `123456`. Everyone else receives a real one-time code
by email; this address accepts the fixed code above, so reviewers never need
access to a mailbox.

### Before every submission

Set these on the production server — **the bypass is off by default** and the
reviewer will be locked out without them:

```
APP_REVIEW_EMAIL=test@apple.com
APP_REVIEW_CODE=123456
```

The employee record is created automatically on first sign-in, as a **Foreman**
(the role whose location is shared with dispatch) joined to the crew with the most
upcoming jobs, so Jobs and Schedule are populated rather than empty. Override
the crew with `APP_REVIEW_CREW_ID` if a specific one shows the app better.

### After approval

**Clear `APP_REVIEW_EMAIL` and `APP_REVIEW_CODE`.** The code is published in this
file and in App Store Connect, so while the bypass is on it is a credential
anyone can use to reach real customer names, addresses, and phone numbers.

---

## Guideline 2.5.4 — background location

### What Apple rejected, and what changed

Build **1.0 (22)** was rejected on 7 September 2026:

> The app declares support for location in the UIBackgroundModes key in your
> Info.plist file but we are unable to locate any features besides employee
> tracking that require persistent location.

That reading was correct. Crew tracking for the Dispatch map was the only use of
the `location` background mode, and Apple does not accept employee tracking as a
reason for it on the public App Store. Arguing the case again would fail, so the
app no longer declares the mode on iOS.

**iOS now reports position only while the app is on screen:**

- `UIBackgroundModes` is **removed entirely** from the iOS Info.plist —
  `plugins/with-background-modes.js` is configured with `"modes": []` and
  deletes the key. `isIosBackgroundLocationEnabled` is `false`, so
  `expo-location` no longer re-adds it.
- The app **no longer requests "Always"** on iOS.
  `NSLocationAlwaysUsageDescription` and
  `NSLocationAlwaysAndWhenInUseUsageDescription` are deleted from the plist too
  (`locationAlwaysPermission: false` in `app.json`); the only location purpose
  string left is `NSLocationWhenInUseUsageDescription`.
- `startLocationUpdatesAsync` / TaskManager are **not used on iOS**. iOS runs a
  `watchPositionAsync` watcher that lives only as long as the app is in the
  foreground, buffers positions, and POSTs a batch to `/api/locations` at most
  once a minute — plus one final report as the app leaves the foreground, so
  dispatch has the freshest possible fix. See `src/lib/location.ts`.
- The Dispatch board already degrades correctly: a crew whose last report is
  older than 15 minutes shows an amber pin reading **"Last known position ·
  last seen 20 minutes ago"** instead of "Live GPS". No server change was
  needed.

Android is unchanged and still reports in the background — that is permitted by
Google Play with the prominent disclosure described in `play-review-notes.md`.
The two platforms deliberately differ; this is not an oversight.

### User-facing copy was corrected to match

Nothing in the app now claims iOS collects location in the background:

- **Profile → Location Sharing** shows, on iOS: "Your crew's location is sent to
  the dispatch office while this app is open… Nothing is sent once you leave the
  app." (`settings.locActiveIos`)
- The pre-prompt **disclosure sheet** drops the "even when the app is closed or
  not in use" sentence on iOS (`location.discloseBodyIos`).
- The "set Location to Always" nudge is Android-only now; iOS can't reach it.

### What to say when replying to the rejection

Paste this into the reply in App Store Connect:

> Thank you for the review. You are right that employee tracking was the only
> feature using persistent location, and we agree that is not an appropriate use
> of the location background mode.
>
> This build removes the `location` background mode from `UIBackgroundModes`
> entirely. The app no longer requests "Always" location authorization — the only
> location purpose string remaining is `NSLocationWhenInUseUsageDescription` —
> and it no longer starts background location updates on iOS. Location is now
> read only while the app is in the foreground, and the app collects no location
> at all once it leaves the screen.

### How to verify in the build

1. Sign in as `test@apple.com` with code `123456` (Foreman).
2. The permission prompt offers **"While Using the App"** only — there is no
   follow-up "Change to Always Allow" prompt.
3. Go to **Profile → Location Sharing**. The card reports sharing while the app
   is open, and "Last sent to dispatch" advances while the app is on screen.
4. Background the app or lock the screen. **No blue location indicator appears**,
   and "Last sent to dispatch" does not advance while the app is away.

## Screen recording checklist (Guideline 2.1)

Apple rejected build 1.0 (17) under **Guideline 2.1 — Information Needed**, asking
for a demo video. This is an information request, not a code defect: nothing in
the app needs to change.

Their requirements, verbatim in effect:

- **A physical iOS device — a simulator recording is rejected.** Use TestFlight
  to install the exact build under review, then iOS's own screen recorder
  (Control Centre → Record).
- Show **location features including background mode when the app is minimised**.
- **Clearly document all relevant app features, services, and user permission
  requests** — note "all", not just location.

**This video cannot be shared with Google Play.** Google rejects iOS footage and
Apple rejects simulator footage, so the two stores need separate recordings. See
`play-review-notes.md` for the Android one.

### Shot list

Permission prompts (Apple asked for every one, not only location):

- [ ] Sign in as the demo Foreman (see the demo account above)
- [ ] **Location** — "While Using the App", then the follow-up **"Change to Always
      Allow"** prompt, accepted on camera
- [ ] **Notifications** — the push permission prompt
- [ ] **Camera** — open a job, add a job-site photo
- [ ] **Photos** — attach an existing image from the library

Location, the part Apple specifically called out. Since 1.0 (23) the point of
this footage is the opposite of what it used to be — it shows that iOS location
is **foreground-only**:

- [ ] The location prompt offering **"While Using the App"**, with **no**
      follow-up "Always Allow" prompt
- [ ] Profile → Location Sharing showing the first "Last sent to dispatch" line
- [ ] Move far enough with the app open to advance it (≥40 m, ≥60 s — see
      `client/src/lib/location.ts`)
- [ ] **Minimise the app / lock the screen** and show that **no blue location
      indicator** appears
- [ ] Reopen and show "Last sent to dispatch" did not advance while the app was
      away

Also worth filming so "all relevant app features" is genuinely covered: Schedule,
Jobs, Time clock in/out, Quotes, and Chat.

### Submitting it

1. Host somewhere Apple can open without signing in (YouTube unlisted, or a
   Google Drive link set to "Anyone with the link"). Verify it in a private
   window while signed out.
2. Paste the link into **App Store Connect → App Review Information → Notes**.
3. **Reply to the rejection message** in App Store Connect — the Notes field
   alone does not reopen the review.

Apple stated that an app reviewable only by demo video needs an **updated video
on every submission**. If nothing has changed, the Notes field must explicitly
confirm the existing video is still valid for all storefronts. Record the URL
here so future submissions can reuse or re-confirm it:

    Video URL: (fill in)

Before submitting, re-check that `APP_REVIEW_EMAIL` / `APP_REVIEW_CODE` are set
on production, or the reviewer cannot sign in at all.
