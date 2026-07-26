# ProjectPulse implementation plan

Updated 2026-07-26. Completion status is substantiated in `docs/final-audit.md`; this document records scope and execution order.

## Requirement checklist

- [x] Laravel/Sanctum API, PostgreSQL-ready schema, seed data, Form Requests, Resources, role/ownership authorization, rate limits, and consistent errors.
- [x] Client, project, member, task, time log, progress note, comment, notification, dashboard, and report endpoints.
- [x] Strict member task transitions plus documented admin corrective transition.
- [x] OpenAI/Gemini abstraction, timeout/retry, response normalization, audit trail, demo fallback, editable review, and transactional bulk save.
- [x] Next.js admin login/dashboard and management screens, AI review, Kanban, reports/CSV, and notifications.
- [x] Ionic/Capacitor Android member login, tasks, status, notes, time, history, notifications, badge, and session handling.
- [x] Backend/web production Dockerfiles and a Compose stack with PostgreSQL, queue, and scheduler.
- [x] Kubernetes namespace, ConfigMap/Secret example, PostgreSQL, migration Job, API/web/worker deployments, Services, probes, resources, Ingress, and HPA.
- [x] GitHub Actions, OpenAPI, Postman, and operational documentation.
- [x] Backend behavior tests, backend formatter, web/mobile lint and production builds, and native Android debug build.

## Dependency map

```text
PostgreSQL
  -> Laravel migrations
  -> Laravel API + queue + scheduler
  -> Next.js admin
  -> Ionic/Capacitor member app

OpenAI or Gemini
  -> provider abstraction
  -> normalized suggestions
  -> admin review
  -> transactional task bulk endpoint
```

## Milestones

1. Contract and data layer: schema, enums, indexes, models, seed data, error envelope.
2. Security and workflows: Sanctum, roles, ownership, validation, state machine, notifications.
3. AI and reporting: providers, audit/failure handling, dashboard aggregates, CSV.
4. Clients: admin web and Android-first member app connected to the real API.
5. Delivery: container images, Compose, Kubernetes, CI, OpenAPI/Postman, final verification.

## Risks and mitigation

| Risk | Mitigation |
|---|---|
| AI downtime or malformed output | Timeout/retry, strict normalization, generic errors, offline demo source, manual tasks remain available |
| Cross-user data access | API query scoping plus feature tests for task/log/note/comment/notification ownership |
| Duplicate reminders | Daily scheduler plus task/deadline idempotency check and automated test |
| Replica migration race | Explicit one-shot migration Job; no migration in container startup |
| Token extraction through client compromise | CSP/XSS hygiene and documented migration to HttpOnly BFF / native secure storage |
| Environment-specific deployment assumptions | `.env.example`, unusable Secret example, local image instructions, probes, and honest audit limitations |

## Acceptance criteria

- A seeded admin can manage the complete client → project → reviewed AI/manual tasks workflow.
- A seeded member can only access assigned work and complete the mobile reporting workflow.
- AI failure does not block manual project/task operations.
- Every protected request requires an active Sanctum identity and sensitive routes require admin or ownership checks.
- Lint/type compilation/tests/builds finish successfully in the documented toolchain.
- Deployment manifests contain no usable secrets or critical placeholders.

## Non-negotiable scope

Authorization, backend validation, state transitions, secret separation, AI review-before-save, migration safety, and truthful verification status may not be traded away for UI polish or bonus features.
