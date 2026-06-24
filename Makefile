.PHONY: build up down shell migrate fresh test logs horizon-logs

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

shell:
	docker compose exec app bash

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

logs:
	docker compose logs -f

horizon-logs:
	docker compose logs -f horizon
