.PHONY: dev stop build setup shell-php shell-vue test test-back test-front test-db-setup migrate seed fresh horizon pint lint logs prod-build prod-up prod-down

# Serviços Docker sem o Vite dev (node) — produção com frontend em frontend/dist
PROD_SERVICES := nginx php-fpm reverb horizon scheduler postgres redis

setup:
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	@test -f frontend/.env || cp frontend/.env.example frontend/.env
	@echo "Arquivos .env criados. Execute 'make dev' e depois 'make shell-php' para key:generate e migrate:fresh --seed"

dev:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

stop:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

build:
	docker compose build

shell-php:
	docker compose exec php-fpm sh

shell-vue:
	docker compose exec node sh

test-db-setup:
	docker compose exec postgres psql -U studytrack -d postgres -c "CREATE DATABASE studytrack_test OWNER studytrack;" 2>/dev/null || true

test: test-back test-front

test-back: test-db-setup
	docker compose exec -e DB_HOST=postgres php-fpm php artisan test

test-front:
	cd frontend && npm run test:run

migrate:
	docker compose exec php-fpm php artisan migrate

seed:
	docker compose exec php-fpm php artisan db:seed

fresh:
	docker compose exec php-fpm php artisan migrate:fresh --seed

horizon:
	docker compose exec horizon php artisan horizon:status

pint:
	docker compose exec php-fpm ./vendor/bin/pint

lint:
	cd frontend && npm run lint

logs:
	docker compose logs -f

prod-build:
	cd frontend && npm ci && npm run build

prod-up:
	docker compose -f docker-compose.yml -f docker-compose.production.yml up -d --build $(PROD_SERVICES)

prod-down:
	docker compose -f docker-compose.yml -f docker-compose.production.yml down
# Rebuild das imagens de sandbox (code execution) com base layers atualizadas
sandbox-rebuild:
	docker compose -f docker/code-sandbox/docker-compose.sandbox.yml build --no-cache

# Rebuild total: todas as imagens customizadas com cache invalidado
rebuild-all: prod-build
	docker compose build --no-cache
	docker compose -f docker/code-sandbox/docker-compose.sandbox.yml build --no-cache

# Backup do PostgreSQL (one-shot ou restore)
backup:
	docker compose exec postgres /backup/backup.sh

backup-restore:
	@echo "Uso: docker compose exec -T postgres psql -U studytrack studytrack < arquivo.sql"
	@echo "Ex: gunzip -c backups/studytrack_20260716_030000.sql.gz | docker compose exec -T postgres psql -U studytrack studytrack"

backup-start:
	docker compose --profile backup up -d pg-backup

# Per-service log targets
logs-nginx:
	docker compose logs -f nginx

logs-php:
	docker compose logs -f php-fpm

logs-reverb:
	docker compose logs -f reverb

logs-horizon:
	docker compose logs -f horizon

logs-scheduler:
	docker compose logs -f scheduler

logs-postgres:
	docker compose logs -f postgres

logs-redis:
	docker compose logs -f redis

logs-node:
	docker compose logs -f node

logs-worker:
	docker compose logs -f horizon scheduler

# Qualidade — pipeline completa (verificação + auto-fix + PR)
quality:
	bash monitoring/quality-pipeline.sh

quality-check:
	bash monitoring/quality-pipeline.sh --no-fix --no-ai
