# Deployment and operations

## Container model

- `backend`: PHP 8.3 FrankenPHP/Caddy, non-root UID 10001, application on port 8000.
- `web`: Next.js standalone Node 20 image, non-root UID 1001, port 3000.
- `postgres`: PostgreSQL 16 with a named Compose volume or Kubernetes PVC.
- `queue`: database queue worker using the backend image.
- `scheduler`: Laravel scheduler using the backend image.

Migrations are intentionally not part of a container entrypoint. This prevents each API replica from racing to migrate the same schema.

## Docker Compose

Create `backend/.env` from the example, generate `APP_KEY`, then:

```bash
docker compose up --build -d
docker compose exec backend php artisan migrate --seed
docker compose ps
docker compose logs -f backend queue scheduler
```

Readiness depends on a successful database connection:

```bash
curl http://localhost:8000/api/health
curl http://localhost:8000/api/health/ready
```

## Local Kubernetes

The manifests assume the images `projectpulse-backend:latest` and `projectpulse-web:latest` already exist in the local cluster image store.

```bash
minikube start
minikube addons enable ingress
minikube addons enable metrics-server
minikube image build -t projectpulse-backend:latest backend
minikube image build -t projectpulse-web:latest --build-arg NEXT_PUBLIC_API_URL=/api web
```

Copy `k8s/secret.example.yaml` to the gitignored `k8s/secret.yaml`, replace all `change-me` values, then run:

```bash
make k8s-apply
```

The target performs this ordered flow:

1. namespace, ConfigMap, and real Secret;
2. PostgreSQL Service and StatefulSet;
3. wait for PostgreSQL rollout;
4. one migration Job and wait for completion;
5. API, queue, scheduler, and web Deployments/Services;
6. Ingress and backend HPA.

The HPA scales the API from 2 to 6 replicas at 70% average CPU and requires metrics-server. Queue and scheduler replicas are managed independently.

## Production adjustments

- Push images with immutable content or commit tags and replace local image names.
- Terminate TLS at the ingress and change `APP_URL`/CORS origins to HTTPS.
- Store secrets in a managed secret store rather than a checked-in YAML file.
- Use managed PostgreSQL with backups, point-in-time recovery, and connection pooling.
- Set a real DNS hostname and configure an ingress certificate.
- Forward structured application logs to centralized storage and add uptime/error alerts.
- Run the migration Job as a gated release step before API rollout.

## Rollback

Application images can be rolled back independently:

```bash
kubectl rollout undo deployment/projectpulse-backend -n projectpulse
kubectl rollout undo deployment/projectpulse-web -n projectpulse
```

Database migrations require migration-specific rollback planning. Do not automatically execute `migrate:rollback` during an application rollback.
