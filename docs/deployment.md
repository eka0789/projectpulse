# ProjectPulse Deployment & Operations Guide

## 1. Docker Compose Setup

Run the entire application stack locally using Docker Compose:

```bash
# 1. Environment Setup
cp backend/.env.example backend/.env
cp web/.env.example web/.env.local

# 2. Build and Start Services
docker compose up --build -d

# 3. Run Database Migrations and Seeders
docker compose exec backend php artisan migrate --seed
```

Access Services:
- Web Admin Dashboard: `http://localhost:3000`
- Backend REST API: `http://localhost:8000/api`
- API Health Check: `http://localhost:8000/api/health`

---

## 2. Kubernetes Local Deployment (Minikube / Kind / k3d)

### Deployment Steps
```bash
# 1. Apply Namespace, ConfigMap, and Secret
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/secret.example.yaml

# 2. Deploy PostgreSQL Database
kubectl apply -f k8s/postgres-statefulset.yaml
kubectl apply -f k8s/postgres-service.yaml

# 3. Run Migration Job
kubectl apply -f k8s/backend-migration-job.yaml

# 4. Deploy Backend API and Web Admin
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/backend-service.yaml
kubectl apply -f k8s/web-deployment.yaml
kubectl apply -f k8s/web-service.yaml

# 5. Apply Ingress & HPA
kubectl apply -f k8s/ingress.yaml
kubectl apply -f k8s/backend-hpa.yaml
```

### Local Ingress DNS Resolution (`hosts` file)
Add the following line to `/etc/hosts` (Linux/macOS) or `C:\Windows\System32\drivers\etc\hosts` (Windows):
```text
127.0.0.1 projectpulse.local api.projectpulse.local
```
