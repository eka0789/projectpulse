.PHONY: setup up down build migrate seed test test-backend test-web test-mobile lint logs clean k8s-apply k8s-delete

setup:
	@echo "Setting up ProjectPulse environment files..."
	cp -n backend/.env.example backend/.env || true
	cp -n web/.env.example web/.env.local || true
	cp -n mobile/.env.example mobile/.env || true

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
	cd web && npm run test -- --run

test-mobile:
	cd mobile && npm run test -- --run

lint:
	cd backend && ./vendor/bin/pint --test || true
	cd web && npm run lint || true
	cd mobile && npm run lint || true

logs:
	docker compose logs -f

clean:
	docker compose down -v
	rm -rf backend/vendor web/node_modules mobile/node_modules

k8s-apply:
	kubectl apply -f k8s/namespace.yaml
	kubectl apply -f k8s/configmap.yaml
	kubectl apply -f k8s/secret.example.yaml
	kubectl apply -f k8s/postgres-statefulset.yaml
	kubectl apply -f k8s/postgres-service.yaml
	kubectl apply -f k8s/backend-migration-job.yaml
	kubectl apply -f k8s/backend-deployment.yaml
	kubectl apply -f k8s/backend-service.yaml
	kubectl apply -f k8s/web-deployment.yaml
	kubectl apply -f k8s/web-service.yaml
	kubectl apply -f k8s/ingress.yaml
	kubectl apply -f k8s/backend-hpa.yaml

k8s-delete:
	kubectl delete -f k8s/ --ignore-not-found
