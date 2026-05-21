# Marshall's Lawn — Crew Field App

Expo (React Native) app for field staff. It mirrors the server's `/mobile`
Livewire views and gives each role a tailored experience:

| Role          | Lands on  | Tabs                                    | Notes |
|---------------|-----------|-----------------------------------------|-------|
| **Foreman**   | Schedule  | Schedule · Jobs · Time · Chat · Profile | Today's crew route in order; start/stop the job clock per stop. Background GPS + chat with the office. |
| **Field**     | Jobs      | Jobs · Time · Profile                   | Assigned jobs + day time clock. |
| **Estimator** | Quotes    | Quotes · Jobs · Profile                 | Create / manage quotes; sees all jobs (read-only). No time clock. |

All roles get the Profile tab (account details + sign out).

The Foreman **Chat** tab is a two-way conversation with the office; staff
reply from the Dispatch screen in the back-office. Messages support photos,
videos, and links, with push notifications when the app is closed.

## Prerequisites

- Node 20+, the Expo CLI (`npx expo`)
- The Laravel API in [`../server`](../server) running with a seeded database

## 1. Run the API

```bash
cd ../server
php artisan migrate
php artisan db:seed --class=MobileDemoSeeder   # demo crew, jobs, route, quotes
php artisan serve                              # http://127.0.0.1:8000
```

The seeder creates three test accounts — all use the password `password`:

- `foreman@marshallslawn.test`
- `field@marshallslawn.test`
- `estimator@marshallslawn.test`

## 2. Run the app

```bash
npm install
npx expo start
```

> **Background GPS**, camera capture, and **push notifications** need a
> **development build** — run `npx expo run:ios` / `npx expo run:android`
> or build with EAS. They do not run in Expo Go; everything else does.
>
> Push notifications also require an EAS project id. Run `eas init` once
> (it adds `extra.eas.projectId` to `app.json`); until then the app runs
> fine but device push tokens won't register.

### Pointing the app at the API

`src/constants/config.ts` defaults to the **live API**
(`https://app.marshallslawninc.com/api`), so release builds and on-device
testing work out of the box.

For local development against a local Laravel server, override it with
`EXPO_PUBLIC_API_URL` in a `.env` file at the client root, then restart
the bundler (`expo start --clear`):

```
# iOS simulator / web
EXPO_PUBLIC_API_URL=http://localhost:8000/api
# Android emulator (host reached via 10.0.2.2)
EXPO_PUBLIC_API_URL=http://10.0.2.2:8000/api
# physical device on the same network
EXPO_PUBLIC_API_URL=http://192.168.1.20:8000/api
```

## Signing in

- **Email / password** — use any seeded employee account above.
- **Developer quick login** — in development the login screen shows
  Foreman / Field / Estimator shortcut buttons that jump straight into
  that role (backed by `POST /api/dev/login`, disabled outside `local`).

## Project layout

```
src/
  app/                       expo-router routes
    (auth)/login.tsx         sign in + dev role switcher
    (app)/(tabs)/            role-aware tabs (schedule, jobs, time, quotes, settings)
    (app)/job/[id].tsx       job detail + foreman start/stop timer
    (app)/quote/[id].tsx     quote editor (edit)
    (app)/quote/new.tsx      quote editor (create)
  components/                ui primitives, tab bar, cards, quote editor
  constants/                 theme tokens, role→tab config, API config
  context/auth.tsx           session state (token + employee)
  lib/                       api client, types, formatting, storage
```

Auth gating uses `Stack.Protected`; the bottom tab bar (`components/tab-bar.tsx`)
renders only the tabs that belong to the signed-in role.
