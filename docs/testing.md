# Testing and verification

## Automated backend coverage

The PHPUnit suite uses an in-memory SQLite database and real Laravel HTTP routing.

- authentication success/failure/inactive account;
- public registration controls, admin boundary, inactive-token rejection, consistent errors/request IDs;
- member task isolation and valid/invalid state transitions;
- client → project → task workflow and assignment notifications;
- client deletion conflict and transactional project/task soft delete;
- dashboard aggregation;
- time-log, progress-note, comment, and notification ownership;
- unassigned-task write rejection;
- OpenAI/Gemini demo fallback, invalid provider payloads, audit rows, and transactional bulk validation;
- task-breakdown normalization bounds;
- H-1 deadline notification idempotency.

Run:

```bash
cd backend
vendor/bin/pint --test
php artisan test
```

## Web verification

The web has strict TypeScript through the Next.js production compiler and ESLint. Its runtime screens expose explicit loading, empty, error, and mutation states.

```bash
cd web
npm run lint
npm run build
```

Component/unit browser tests are not currently included; this is recorded as a limitation rather than represented as completed coverage.

## Mobile verification

```bash
cd mobile
npm run lint
npm run build
npx cap sync android
cd android
gradlew.bat assembleDebug
```

The native build requires JDK 21 and an installed Android SDK. Device-level UI automation is not included.

## Infrastructure verification

Where Docker/Kubernetes CLIs are available:

```bash
docker compose config
docker compose build
kubectl apply --dry-run=client -f k8s/
```

The migration Job and Secret example require their documented ordered deployment flow; do not apply every YAML file alphabetically in production.
