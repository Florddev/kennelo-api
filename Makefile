.PHONY: help main update update-deps start down infra infra-down larastan

help: ## Show this message
	@echo "Available commands:"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z_-]+:.*##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

main: ## Checkout main branch && git pull
	git checkout main && git pull

update: ## Install deps Back/Front
	cd api && composer install
	cd api && php artisan storage:link
	pnpm install

update-deps: ## Bump deps + update locks
	cd api && composer update
	pnpm update

infra: ## Start dev services (postgres, redis, minio) via Docker
	docker compose -f docker-compose.dev.yml up -d

infra-down: ## Stop dev services (postgres, redis, minio)
	docker compose -f docker-compose.dev.yml down

start: ## Start API, Web and Back-office
	cd api && PHP_CLI_SERVER_WORKERS=8 php -d upload_max_filesize=50M -d post_max_size=55M artisan serve --host=0.0.0.0 --port=8000 &
	cd api && php artisan queue:work --queue=default --tries=1 --memory=1024 --timeout=180 &
	cd api && php artisan reverb:start --host=0.0.0.0 --port=8080 &
	pnpm --filter web dev &
	pnpm --filter base-nextjs dev &

down: ## Stop API and Web (serve, queue, reverb, turbo, next)
ifeq ($(OS),Windows_NT)
	-@powershell -NoProfile -ExecutionPolicy Bypass -File scripts/kill-dev-ports.ps1
else
	-pkill -f "artisan serve"
	-pkill -f "artisan queue:work"
	-pkill -f "artisan reverb:start"
	-pkill -f "turbo dev"
	-pkill -f "next dev"
	-pkill -f "generate-routes-watch"
	-pkill -f "scripts/watch.mjs"
	-for p in 8000 8080 3000 3001; do lsof -ti :$$p | xargs kill -9 2>/dev/null || true; done
endif

larastan: ## Run larastan
	cd api && ./vendor/bin/phpstan analyse --memory-limit=2G

setup:
	cd api && cp .env.example .env
	cd api && composer install
	cd api && php artisan key:generate
	cd api && php artisan jwt:generate
	cd api && make refresh
	make update

refresh: ## Refresh DB and seed
	cd api && php artisan migrate:fresh --seed

stripe:
	stripe listen --forward-to localhost:8000/api/webhooks/stripe