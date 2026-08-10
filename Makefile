.PHONY: dev stop build setup shell-php shell-vue test test-back test-front test-e2e test-e2e-headed zap-scan lighthouse test-db-setup migrate seed fresh horizon pint lint logs prod-build prod-up prod-down security-audit health clean deploy math-up math-test math-logs

# Serviços Docker sem o Vite dev (node) — produção com frontend em frontend/dist
PROD_SERVICES := nginx php-fpm reverb horizon scheduler math-service postgres redis

setup:
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	@test -f frontend/.env || cp frontend/.env.example frontend/.env
	@echo "Arquivos .env criados. Execute 'make dev' e depois 'make shell-php' para key:generate e migrate:fresh --seed"

dev-setup: setup
	@echo "Building Docker images..."
	docker compose build
	@echo "Starting database and cache..."
	docker compose up -d postgres redis
	@echo "Waiting for PostgreSQL..."
	@sleep 5
	docker compose exec postgres pg_isready -U studytrack || sleep 3
	@echo "Generating app key..."
	docker compose run --rm php-fpm php artisan key:generate --force
	@echo "Running migrations and seeding..."
	docker compose run --rm php-fpm php artisan migrate:fresh --seed
	@echo ""
	@echo "=== Setup complete! Run 'make dev' to start. ==="

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

# E2E — testa a aplicação em execução (Playwright). Pré-requisito: app no ar (make dev).
test-e2e:
	cd frontend && npx playwright test

test-e2e-headed:
	cd frontend && npx playwright test --headed

# Segurança DAST — OWASP ZAP full scan contra a app em execução (relatório em monitoring/reports/)
# Uso: make zap-scan | make zap-scan ARGS=--baseline
zap-scan:
	bash monitoring/zap-scan.sh $(ARGS)

# UI/UX — auditoria Lighthouse (performance, a11y, SEO) da app em execução.
# Usa o Chrome headless do Playwright (já instalado em frontend/node_modules).
lighthouse:
	cd frontend && CHROME_PATH="$$(ls -d ~/.cache/ms-playwright/chromium_headless_shell-*/chrome-headless-shell-linux64/chrome-headless-shell 2>/dev/null | head -1)" npx lighthouse http://localhost:8080 --output html --output-path ../monitoring/reports/lighthouse.html --chrome-flags="--headless --no-sandbox"

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

# ========================
# Math service (FastAPI + SymPy)
# ========================
math-up:
	docker compose up -d --build math-service

math-test:
	docker compose exec math-service python -m pytest -q

math-logs:
	docker compose logs -f math-service

# ========================
# Frontend
# ========================
# Instala dependências DENTRO do container node (volume node_modules separado do host;
# npm install no host não afeta o container). O entrypoint roda npm ci sozinho no restart
# quando o package-lock.json diverge (cache npm em /app/.npm-cache via NPM_CONFIG_CACHE).
frontend-deps:
	docker compose exec node npm install

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


type-check:
	cd frontend && npm run type-check

test-coverage:
	docker compose exec -e DB_HOST=postgres php-fpm php artisan test --coverage
	cd frontend && npm run test:coverage

db-reset:
	docker compose exec php-fpm php artisan migrate:fresh --seed

routes:
	docker compose exec php-fpm php artisan route:list

# Qualidade — pipeline completa (verificação + auto-fix + PR)
quality:
	bash monitoring/quality-pipeline.sh

quality-check:
	bash monitoring/quality-pipeline.sh --no-fix --no-ai

# MiMo Schedule — execução agendada de prompts AI
mimo-schedule:
	bash monitoring/mimo-schedule.sh --once

mimo-schedule-list:
	bash monitoring/mimo-schedule.sh --list

mimo-schedule-run:
	@echo "Uso: make mimo-schedule-run PROMPT=<nome>"
	@echo "Ex: make mimo-schedule-run PROMPT=test-backend"
	@test -n "$(PROMPT)" || (echo "Erro: PROMPT não definido" && exit 1)
	bash monitoring/mimo-schedule.sh --run $(PROMPT)

# Security — auditoria de dependências e código
security-audit:
	docker compose exec php-fpm composer audit
	cd frontend && npm audit --production
	bash monitoring/check-security.sh

# Health — verificação rápida de saúde
health:
	@curl -sf http://localhost:8080/api/health | python3 -m json.tool 2>/dev/null || echo "API não disponível"

# Clean — limpeza de recursos Docker não utilizados
clean:
	docker system prune -f
	docker volume prune -f

# Deploy — pull de imagens e restart em produção (via SSH)
deploy:
	@echo "Deploy é feito via CI/CD (push para main)."
	@echo "Para deploy manual no servidor:"
	@echo "  cd \$$PROJECT_DIR && docker compose -f docker-compose.yml -f docker-compose.production.yml pull && docker compose -f docker-compose.yml -f docker-compose.production.yml up -d"
