# ProjectPulse Testing Strategy

## 1. Backend Testing Strategy (PHPUnit / Pest)

### Feature Tests (`tests/Feature`)
- **Authentication**: `AuthTest.php` (login success, invalid credentials, token revocation, inactive user check).
- **Client Management**: `ClientTest.php` (Admin CRUD, Member access forbidden).
- **Project Management**: `ProjectTest.php` (Admin CRUD, status filters, client relationship).
- **Task Management & Authorization**: `TaskTest.php` (assignment, task state machine transitions, member scope isolation).
- **Time Logs & Workload**: `TimeLogTest.php` (duration validation max 24h, member scope isolation).
- **AI Task Breakdown**: `AITaskBreakdownTest.php` (mock provider, prompt parsing, resiliency on provider timeout/failure).
- **Notifications & Scheduler**: `NotificationTest.php` (assignment notification, idempotent H-1 deadline job).

---

## 2. Web & Mobile Client Testing Strategy

- **Web Admin Tests**: Vitest / React Testing Library covering Login form, Task Kanban drag-and-drop state rollback, AI suggestion editor, and dashboard charts.
- **Mobile Member App Tests**: Vitest / React Testing Library covering mobile auth state, task filtering, status update actions, time log input validation, and unread notification badges.
