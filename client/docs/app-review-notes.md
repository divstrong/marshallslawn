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
(the role that has background location) joined to the crew with the most
upcoming jobs, so Jobs and Schedule are populated rather than empty. Override
the crew with `APP_REVIEW_CREW_ID` if a specific one shows the app better.

### After approval

**Clear `APP_REVIEW_EMAIL` and `APP_REVIEW_CODE`.** The code is published in this
file and in App Store Connect, so while the bypass is on it is a credential
anyone can use to reach real customer names, addresses, and phone numbers.

---

## Guideline 2.5.4 — background location

### Why the app declares the `location` background mode

Marshall's Lawn is a dispatch tool for landscaping crews. The office runs a live
Dispatch map showing where every crew currently is, and assigns the next job to
the nearest crew. That requires the crew's phone to report position while the app
is backgrounded and the screen is locked — a foreman is driving a truck or running
a mower, not holding the phone with the app open.

Concretely:

- A signed-in **Foreman** or **Spray Tech** reports position continuously via
  `startLocationUpdatesAsync` — roughly every 60 seconds or every 40 metres.
- Each batch is POSTed to `/api/locations` and stored as a breadcrumb trail.
- The office's Dispatch board renders each crew's latest position and marks it
  "live" only if it arrived within the last **15 minutes**. Without background
  updates, a crew goes stale within 15 minutes of the phone locking and the
  board stops being usable for dispatching.
- `showsBackgroundLocationIndicator` is enabled, so iOS shows the blue location
  indicator the whole time tracking runs.

Significant-change and region monitoring were considered and are not sufficient:
both report only on large displacements, which cannot show a crew's position on
a route with the freshness the dispatch board needs.

### What changed in this build

`UIBackgroundModes` previously contained `fetch` as well as `location`. The
`fetch` entry was added automatically by `expo-task-manager` simply by being
installed; the app never registered a background fetch task. It has been removed,
so the app now declares only `location`, which it genuinely uses. See
`client/plugins/with-background-modes.js`.

The location permission strings were also rewritten to describe the actual
behaviour (continuous background reporting to dispatch) rather than implying it
only runs "on the clock".

### How to see the feature

1. Sign in as `test@apple.com` with code `123456` (Foreman).
2. Accept the location prompt, choosing **Always Allow**.
3. Go to the **Profile** tab → **Location Sharing**.
   The card shows a live status plus the timestamp and coordinates of the most
   recent report actually delivered to dispatch.
4. Background the app or lock the screen and walk/drive a short distance.
   The blue location indicator stays visible.
5. Reopen the app and return to **Profile → Location Sharing**. The
   "Last sent to dispatch" line has advanced, which only happens from a report
   delivered while the app was backgrounded.

---

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

Background location, the part Apple specifically called out:

- [ ] Profile → Location Sharing showing the first "Last sent to dispatch" line
- [ ] **Minimise the app / lock the screen**, with the **blue location indicator**
      visible in the status bar the whole time
- [ ] Move far enough to trigger an update (≥40 m, ≥60 s — see
      `client/src/lib/location.ts`)
- [ ] Reopen and show the "Last sent to dispatch" timestamp and coordinates have
      changed while the app was backgrounded

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
