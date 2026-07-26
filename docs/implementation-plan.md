# ProjectPulse Implementation Plan

> Progress snapshot (2026-07-26): checkboxes below reflect verified repository
> evidence. A feature is not marked complete solely because a route or document
> exists. Current verified vertical slice: Sanctum login, protected admin shell,
> and live dashboard summary. CRUD web screens and the mobile app remain pending.

## 1. Requirement Checklist

### Role & Access Control
- [x] Admin role: Web access, manage clients, projects, tasks, assignees, deadlines, AI task breakdown review, Kanban, time logs, reports.
- [x] Member role: Mobile access, view assigned tasks, task details, update status, progress notes, time logs, task history, notifications.
- [x] Backend authorization: Enforce role-based policies on all sensitive endpoints (prevent members from viewing other members' tasks or accessing client/project management).

### Database & Data Models
- [x] `users`: id, name, email, password, role (admin, member), job_title, avatar_url, is_active, timestamps.
- [x] `clients`: id, name, company, email, phone, address, notes, created_by, timestamps, soft deletes.
- [x] `projects`: id, client_id, name, description, client_brief, start_date, deadline, status (draft, active, on_hold, completed, cancelled), created_by, timestamps, soft deletes.
- [x] `tasks`: id, project_id, title, description, category (frontend, backend, design, qa, devops, management, other), assignee_id, priority (low, medium, high, urgent), status (todo, in_progress, review, done), estimated_hours, start_date, deadline, completed_at, created_by, source (manual, ai), timestamps, soft deletes.
- [x] `time_logs`: id, task_id, user_id, work_date, duration_minutes, note, timestamps.
- [x] `progress_notes`: id, task_id, user_id, note, status_snapshot, timestamps.
- [x] `task_comments`: id, task_id, user_id, body, edited_at, timestamps, soft deletes.
- [x] `notifications`: id, user_id, type, title, message, data, read_at, timestamps.
- [x] `ai_task_generations`: id, project_id, requested_by, provider, model, brief_hash, request_payload, response_payload, status, error_code, error_message, latency_ms, timestamps.

### Authentication & Security
- [x] Laravel Sanctum Token Authentication (`POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`, `POST /api/auth/register`).
- [x] Password hashing, login rate limiting, API rate limiting, role middleware, policies.
- [x] Strict state transitions for tasks (`todo -> in_progress -> review -> done` & `review -> in_progress`).
- [x] Global exception handling & standardized API response format.

### AI Integration
- [x] Interface-driven `TaskBreakdownProviderInterface` (OpenAI / Gemini implementations).
- [x] Assistive & resilient AI flow: client brief -> backend validation -> AI provider call -> structured JSON parsing -> admin review & edit -> bulk insert transaction.
- [x] Fallback mode (`AI_DEMO_FALLBACK_ENABLED`) and non-blocking failure responses (`AI_PROVIDER_UNAVAILABLE`).

### Notification & Scheduler
- [x] Notifications for task assignment, status change, comments, and deadline alerts.
- [x] Idempotent H-1 upcoming task deadline notification command (`SendUpcomingTaskDeadlineNotifications`).

### Frontend Apps
- [x] Web foundation: Next.js App Router, strict TypeScript, Tailwind, reusable UI primitives, TanStack Query, Axios, RHF/Zod, responsive protected shell.
- [x] Web authentication and dashboard: real Laravel API login/logout/me, centralized bearer token and 401 handling, live dashboard metrics/chart/workload/recent projects, loading/error/empty states.
- [ ] Web management: Client CRUD, Project CRUD, AI review wizard, Task CRUD, Kanban, members, reports, and notifications.
- [ ] Mobile Member App: Ionic React / Capacitor login, My Tasks, Task Detail, Status Update, Time Log, History, and Notifications.

### DevOps & Infrastructure
- [x] Dockerfiles for Backend & Web Admin (web uses a non-root standalone Next.js runtime).
- [ ] `docker-compose.yml` defines PostgreSQL, Backend, Web Admin, Queue, and Scheduler, but runtime validation is pending because Docker is not installed in the current environment.
- [ ] Kubernetes manifests: present only in part and still require PostgreSQL StatefulSet, migration Job, HPA, resource limits, and end-to-end validation.
- [ ] CI Workflow: not yet verified.
- [ ] Postman Collection & Environment: not yet verified.

---

## 2. Milestones & Implementation Timeline

| Day / Phase | Key Milestone | Primary Deliverables |
|---|---|---|
| **Phase 1** | Foundation & Backend API | Laravel Sanctum setup, Migrations, Models, Policies, Resources, Controllers, Seeders, Feature Tests |
| **Phase 2** | AI Integration & Web Admin | Provider abstraction, Task Breakdown Service, Next.js Web Admin pages, Dashboard, Kanban, AI review wizard |
| **Phase 3** | Mobile App & Workflows | Ionic React mobile app, Authentication, Task list, Detail, Time Log, Progress Notes, Notifications |
| **Phase 4** | DevOps, Testing & Audit | Docker, Compose, K8s manifests, Postman Collection, CI workflow, Comprehensive Documentation & Final Audit |

---

## 3. Risk Management & Acceptance Criteria

| Risk | Mitigation Strategy |
|---|---|
| AI API rate limits / downtime | Implement timeout (20s), strict fallback handler, and non-blocking response format. Admin can always manually create tasks. |
| Dual client types (Web & Mobile) | Shared OpenAPI standard REST endpoints, token revocation, strict backend authorization check for roles. |
| Duplicate deadline notifications | Idempotency key tracking via database notification checks before dispatching. |
| Invalid task state transitions | Enforce state transition rules in `TaskStateService` and return `422 Unprocessable Entity` on invalid state change. |

---

## 4. Implementation Order

1. Complete Architecture, Database Schema, API, Security, Testing & Decisions Documentation in `docs/`.
2. Build Laravel Backend foundation (`app/`, `database/`, `routes/api.php`, `tests/`).
3. Build Web Admin (`web/src/` with React/Next.js components, services, and pages).
4. Build Mobile App (`mobile/src/` with Ionic React components, stores, pages).
5. Configure DevOps & Infrastructure (`docker-compose.yml`, Dockerfiles, `k8s/` manifests, CI).
6. Generate Postman collection & environment.
7. Conduct full testing and produce `docs/final-audit.md`.
