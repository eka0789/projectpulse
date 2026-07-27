# ProjectPulse API Documentation

## Standard Response Structure

### Success Response Format
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {},
  "meta": null
}
```

### Paginated Collection Response Format
```json
{
  "success": true,
  "message": "Data retrieved successfully.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4
  }
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Validation failed.",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "deadline": [
        "The deadline must be a date after today."
      ]
    }
  }
}
```

---

## Endpoint Summary

### Authentication (`/api/auth`)
- `POST /api/auth/login` — Public login endpoint, returns Bearer token and user info.
- `POST /api/auth/register` — Development / Admin registration endpoint.
- `POST /api/auth/logout` — Revokes current Sanctum token.
- `GET  /api/auth/me` — Fetches active user details.

### Members (`/api/members`) — Admin Only
- `GET    /api/members` — List system team members.
- `POST   /api/members` — Create new team member.
- `GET    /api/members/{id}` — Get member detail.
- `PATCH  /api/members/{id}` — Update member profile or active state.
- `DELETE /api/members/{id}` — Soft delete / deactivate member.

### Clients (`/api/clients`) — Admin Only
- `GET    /api/clients` — Search, filter, paginate clients.
- `POST   /api/clients` — Create new client.
- `GET    /api/clients/{id}` — Get client details & project count.
- `PATCH  /api/clients/{id}` — Update client metadata.
- `DELETE /api/clients/{id}` — Soft delete client (blocked if active projects exist).

### Projects (`/api/projects`) — Admin Access / Member View
- `GET    /api/projects` — Filter projects by status, client, deadline.
- `POST   /api/projects` — Create project (Admin).
- `GET    /api/projects/{id}` — Project overview & task breakdown summary.
- `PATCH  /api/projects/{id}` — Update project status / info (Admin).
- `DELETE /api/projects/{id}` — Soft delete project (Admin).

### AI Task Breakdown (`/api/projects/{project}/tasks/generate`) — Admin Only
- `POST /api/projects/{project}/tasks/generate` — Generate AI task breakdown suggestions from brief.
- `POST /api/projects/{project}/tasks/bulk` — Save approved list of AI & manual tasks in a single DB transaction.

### Tasks (`/api/tasks` & `/api/projects/{project}/tasks`)
- `GET    /api/projects/{project}/tasks` — List tasks inside a project.
- `POST   /api/projects/{project}/tasks` — Add single task to project (Admin).
- `GET    /api/tasks` — List tasks with filters (Admin views all, Member views assigned tasks only).
- `GET    /api/tasks/{id}` — Task details, time logs, progress notes, comments.
- `PATCH  /api/tasks/{id}` — Update task details, assignee, deadline (Admin only for assignment/deadline).
- `DELETE /api/tasks/{id}` — Soft delete task (Admin).
- `PATCH  /api/tasks/{id}/status` — Transition task state (`todo -> in_progress -> review -> done` & `review -> in_progress`).

### Time Logs (`/api/tasks/{task}/time-logs` & `/api/time-logs/{id}`)
- `GET    /api/tasks/{task}/time-logs` — Get time entries for a task.
- `POST   /api/tasks/{task}/time-logs` — Log work duration in minutes (max 24 hrs per log).
- `PATCH  /api/time-logs/{id}` — Edit existing time log (Owner or Admin).
- `DELETE /api/time-logs/{id}` — Delete time log (Owner or Admin).

### Progress Notes & Comments
- `GET    /api/tasks/{task}/progress-notes` & `POST /api/tasks/{task}/progress-notes`
- `GET    /api/tasks/{task}/comments` & `POST /api/tasks/{task}/comments`

### Notifications (`/api/notifications`)
- `GET    /api/notifications` — List notifications for active user.
- `GET    /api/notifications/unread-count` — Count unread alerts.
- `PATCH  /api/notifications/{id}/read` — Mark single notification read.
- `PATCH  /api/notifications/read-all` — Mark all notifications read.

### Dashboard & Reports (`/api/dashboard` & `/api/reports`)
- `GET /api/dashboard/summary` — Active projects, overdue tasks, status breakdown, member workload matrix.
- `GET /api/reports/time-logs` — Detailed report on time logs filtered by project, user, date range.
- `GET /api/reports/time-logs/export.csv` — CSV export of time log report.
- `GET /api/reports/time-logs/export.pdf` — PDF export of time log report.
- `GET /api/reports/time-logs/export.pdf` — PDF export of time log report.
- `GET /api/health` & `GET /api/health/ready` — Liveness & Readiness health check endpoints.
