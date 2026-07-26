# ProjectPulse Mobile

Ionic React/Capacitor Android client for ProjectPulse members.

## Features

- Sanctum bearer-token login and session restore
- member-only role guard and logout
- assigned-task list with status filters and pull-to-refresh
- task detail, valid status actions, time logging, and progress notes
- completed-task history
- notification inbox with unread badge and mark-read actions
- loading, empty, API error, validation, and offline states

## Run

```bash
npm ci
copy .env.example .env
npm run dev
```

Use `cp` rather than `copy` on macOS/Linux.

Android emulator:

```bash
npm run build
npx cap sync android
npx cap open android
```

Capacitor 7 requires JDK 21. The default emulator API URL is `http://10.0.2.2:8000/api`; change `VITE_API_URL` for a physical device.

## Verify

```bash
npm run lint
npm run build
cd android
gradlew.bat assembleDebug
```

Ionic Storage is used for the requested demo architecture. Before a public app-store release, use a Keychain/Android Keystore-backed storage plugin for the bearer token.
