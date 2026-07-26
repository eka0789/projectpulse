# ProjectPulse Technical Decisions & Trade-offs

## 1. Architectural Decisions

### Database Notifications vs WebSockets
- **Decision**: Utilized Laravel Database Notifications with HTTP polling for mobile/web.
- **Rationale**: For the scope of a technical evaluation, database notifications provide robust persistence, transaction safety, and simple querying without requiring external WebSocket server infrastructure (Soketi / Pusher).

### Synchronous AI Request vs Async Queued AI Generation
- **Decision**: Synchronous HTTP POST request with a 20-second timeout and strict fallback.
- **Rationale**: Administrative task breakdown is an interactive step during project creation. Real-time feedback allows the admin to immediately view, tweak, and approve generated tasks in the wizard UI. If AI fails, the non-blocking fallback returns immediately so project setup is never delayed.

### Sanctum Token Authentication vs OAuth2 / Keycloak
- **Decision**: Laravel Sanctum Personal Access Tokens.
- **Rationale**: Sanctum provides lightweight, secure token authentication suitable for mobile clients and SPA web apps without the operational overhead of a full OAuth2 server.

---

## 2. Technical Assumptions & Trade-offs

1. **Client Deletion Strategy**: Clients with active projects cannot be hard-deleted. Soft deletes are enforced to preserve historical project and task reporting data.
2. **Task State Machine**: Enforces rigid linear progression (`todo -> in_progress -> review -> done`). Corrective moves back to `in_progress` are allowed from `review`. Admin override transitions are supported via administrative API endpoints.
3. **Database Migration Strategy in Kubernetes**: Handled via a Kubernetes `Job` (`k8s/backend-migration-job.yaml`) executing before deployment rollout to prevent concurrent schema migration race conditions between backend replicas.
