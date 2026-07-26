# ProjectPulse Security Architecture

## 1. Authentication & Token Management
- **Token Mechanism**: Uses Laravel Sanctum personal access tokens. Tokens are transmitted in HTTP `Authorization: Bearer <token>` headers.
- **Token Revocation**: Tokens are explicitly deleted from `personal_access_tokens` table upon `POST /api/auth/logout`.
- **Session Auto-Recovery**: Mobile app securely stores token in Capacitor storage / localStorage and auto-validates session via `GET /api/auth/me` on startup.

---

## 2. Authorization & Role Enforcement
- System defines two strict roles: `admin` and `member`.
- Authorization rules are enforced strictly in backend Policy classes (`ClientPolicy`, `ProjectPolicy`, `TaskPolicy`, `TimeLogPolicy`):
  - **Admin**: Full access across clients, projects, task assignments, member workload views, and AI task generation.
  - **Member**: Restricted exclusively to tasks assigned to them (`tasks.assignee_id = auth_id`). Cannot create clients, create projects, reassign tasks, or view other members' time logs.

---

## 3. Data Integrity & Validation
- **Form Request Validation**: All incoming API requests undergo server-side validation.
- **Task State Machine**: Status transitions are restricted (`todo -> in_progress -> review -> done` & `review -> in_progress`). Invalid transitions trigger `422 Unprocessable Entity`.
- **SQL Injection & XSS Protection**: All queries utilize Eloquent ORM parameterized queries. All user input rendered in frontend interfaces is escaped natively by React JSX and Next.js.
- **Sensitive Data Logging**: Passwords, auth tokens, and LLM API keys are stripped from log contexts.
