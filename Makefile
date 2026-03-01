.PHONY: dev stop build setup shell-php shell-vue test test-back test-front test-db-setup migrate seed fresh horizon pint lint logs

setup:
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	@test -f frontend/.env || cp frontend/.env.example frontend/.env
	@echo "Arquivos .env criados. Execute 'make dev' e depois 'make shell-php' para key:generate e migrate:fresh --seed"

dev:
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

stop:
	docker compose down

build:
	docker compose build

shell-php:
	docker compose exec php-fpm bash

shell-vue:
	docker compose exec node bash

test-db-setup:
	docker compose exec postgres psql -U studytrack -d postgres -c "CREATE DATABASE studytrack_test OWNER studytrack;" 2>/dev/null || true

test: test-back test-front

test-back: test-db-setup
	docker compose exec php-fpm php artisan test

test-front:
	cd frontend && npm run test:run

migrate:
	docker compose exec php-fpm php artisan migrate

seed:
	docker compose exec php-fpm php artisan db:seed

fresh:
	docker compose exec php-fpm php artisan migrate:fresh --seed

horizon:
	docker compose exec php-fpm php artisan horizon

pint:
	docker compose exec php-fpm ./vendor/bin/pint

lint:
	cd frontend && npm run lint

logs:
	docker compose logs -f
