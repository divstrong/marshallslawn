# Marshall's Lawn — Crew Field App

Expo (React Native) app for field staff. It mirrors the server's `/mobile`
Livewire views and gives each role a tailored experience:

| Role          | Lands on  | Tabs                             | Notes |
|---------------|-----------|----------------------------------|-------|
| **Foreman**   | Schedule  | Schedule · Jobs · Time · Profile | Today's crew route in order; start/stop the job clock per stop. |
| **Field**     | Jobs      | Jobs · Time · Profile            | Assigned jobs + day time clock. |
| **Estimator** | Quotes    | Quotes · Jobs · Profile          | Create / manage quotes; sees all jobs (read-only). No time clock. |

All roles get the Profile tab (account details + sign out).

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

> **Background GPS** (foreman location tracking) and full camera support
> need a **development build** — run `npx expo run:ios` / `npx expo run:android`
> or build with EAS. They do not run in Expo Go; everything else does.

### Pointing the app at the API

`src/constants/config.ts` defaults to:

- iOS simulator / web → `http://localhost:8000/api`
- Android emulator → `http://10.0.2.2:8000/api`

On a **physical device**, set the host machine's LAN IP via an env var
(e.g. in a `.env` file at the client root):

```
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
