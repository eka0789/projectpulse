.PHONY: setup install up down build migrate seed test test-backend test-web test-mobile lint logs clean k8s-apply k8s-delete

setup:
	@echo "Setting up ProjectPulse environment files..."
	cp -n backend/.env.example backend/.env || true
	cp -n web/.env.example web/.env.local || true
	cp -n mobile/.env.example mobile/.env || true

install:
	cd backend && composer install
	cd web && npm ci
	cd mobile && npm ci

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up --build -d

migrate:
	docker compose exec backend php artisan migrate

seed:
	docker compose exec backend php artisan db:seed

test: test-backend test-web test-mobile

test-backend:
	cd backend && php artisan test

test-web:
	cd web && npm run lint && npm run build

test-mobile:
	cd mobile && npm run lint && npm run build

lint:
	cd backend && ./vendor/bin/pint --test
	cd web && npm run lint
	cd mobile && npm run lint

logs:
	docker compose logs -f

clean:
	docker compose down -v
	rm -rf backend/vendor web/node_modules mobile/node_modules

k8s-apply:
	test -f k8s/secret.yaml
	kubectl apply -f k8s/namespace.yaml
	kubectl apply -f k8s/configmap.yaml
	kubectl apply -f k8s/secret.yaml
	kubectl apply -f k8s/postgres-statefulset.yaml
	kubectl apply -f k8s/postgres-service.yaml
	kubectl rollout status statefulset/projectpulse-postgres -n projectpulse --timeout=180s
	kubectl apply -f k8s/backend-migration-job.yaml
	kubectl wait --for=condition=complete job/projectpulse-migrate -n projectpulse --timeout=180s
	kubectl apply -f k8s/backend-deployment.yaml
	kubectl apply -f k8s/backend-service.yaml
	kubectl apply -f k8s/backend-workers.yaml
	kubectl apply -f k8s/web-deployment.yaml
	kubectl apply -f k8s/web-service.yaml
	kubectl apply -f k8s/ingress.yaml
	kubectl apply -f k8s/backend-hpa.yaml

k8s-delete:
	kubectl delete -f k8s/ --ignore-not-found
