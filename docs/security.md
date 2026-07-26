# Security model

## Authentication

- Laravel Sanctum personal-access tokens are sent as `Authorization: Bearer`.
- Login errors are generic; inactive users cannot log in.
- Login/register are limited to 5 requests per minute and authenticated API routes to 60 per minute.
- Logout revokes the current token; deactivating a member revokes all of that user's tokens.
- Public registration is disabled by default and, when enabled for development, can only create members.

## Authorization

The backend is the enforcement boundary:

- `admin` middleware protects dashboard, members, clients, projects, reports, task administration, bulk task creation, and AI generation.
- member task queries are always scoped to `assignee_id = authenticated user`.
- task detail, status, time log, progress note, comment, and notification operations verify ownership/assignment.
- members cannot mutate assignees or deadlines.
- the last active admin and an admin's own active session cannot be accidentally deactivated through member management.

Policies cover core client/project/task rules; endpoint-specific ownership rules are enforced close to the affected query.

## Validation and data integrity

- Form Requests define write validation and normalize API validation errors.
- Task member transitions are restricted to `todo -> in_progress -> review -> done` and `review -> in_progress`.
- Admin corrective transitions are allowed through the authenticated status endpoint and recorded by normal model timestamps.
- Per-entry time is limited to 1–1440 minutes.
- Briefs, notes, descriptions, comments, and paging sizes are bounded.
- Assignees must be active members.
- Multi-task creation and project/task soft-deletion use transactions.
- Eloquent query binding prevents raw input interpolation into SQL values.

## API and transport

- CORS origins come from `CORS_ALLOWED_ORIGINS`.
- API errors do not expose stack traces in production.
- readiness failures do not expose driver exception text.
- responses include `X-Request-ID` for correlation.
- TLS is expected at the production ingress.

## AI safety

- Provider keys come only from environment/Secret values.
- API tokens/keys and raw provider HTTP bodies are not written to logs.
- audit rows store provider/model, brief hash, bounded request/response data, status, latency, and generic error classification.
- provider calls have timeouts and bounded retry; responses are parsed, normalized, enumerated, and size-limited.
- AI output remains an editable suggestion until an admin submits the bulk endpoint.

## Client token-storage trade-offs

The mobile app uses Ionic Storage and the web app uses local storage to meet the shared bearer-token test architecture. These stores can be read by compromised application JavaScript. A public mobile release should use Keychain/Keystore-backed storage; a public web deployment should consider a same-origin backend-for-frontend with secure, HttpOnly, SameSite cookies.

## Secret management

`.env`, `backend/.env`, `k8s/secret.yaml`, build outputs, and dependency folders are ignored. `secret.example.yaml` is intentionally unusable. Repository scanning and dependency-advisory checks should be enabled in the hosting platform before public release.
