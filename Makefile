.PHONY: dev stop build shell-php shell-vue test test-db-setup migrate fresh

dev:
	docker compose up -d

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

test: test-db-setup
	docker compose exec php-fpm php artisan test
	cd frontend && npm run test:run

migrate:
	docker compose exec php-fpm php artisan migrate

fresh:
	docker compose exec php-fpm php artisan migrate:fresh --seed
