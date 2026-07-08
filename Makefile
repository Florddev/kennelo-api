.PHONY: help main update update-deps start down infra infra-down larastan test-e2e test-e2e-ui e2e-serve test-e2e-ui-reuse e2e-down

help: ## Show this message
	@echo "Available commands:"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z0-9_-]+:.*##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

main: ## Checkout main branch && git pull
	git checkout main && git pull

update: ## Install deps Back/Front
	cd api && composer install
	cd api && php artisan storage:link
	pnpm install
	pnpm --filter @workspace/translations build

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

test-e2e: ## Run web E2E tests (builds an isolated DB + servers, zero-trace teardown)
	pnpm --filter web test:e2e

test-e2e-ui: ## Run web E2E tests in Playwright UI mode (auto-starts servers on first run)
	pnpm --filter web test:e2e:ui

e2e-serve: ## Start the E2E test DB + API + web and keep them warm (for reuse mode)
	cd apps/web && node e2e/support/serve-api.mjs &
	cd apps/web && env $$(grep -E '^NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=' .env | head -1) \
		NEXT_PUBLIC_API_URL=http://localhost:8000/api \
		NEXT_PUBLIC_ROUTE_MODE=dynamic \
		NEXT_PUBLIC_PLATFORM=web \
		NEXT_PUBLIC_REVERB_APP_KEY=e2e-key \
		NEXT_PUBLIC_REVERB_HOST=localhost \
		NEXT_PUBLIC_REVERB_PORT=8080 \
		NEXT_PUBLIC_REVERB_SCHEME=http \
		pnpm --filter web dev &
	@echo "E2E servers starting — API :8000, Web :3000. Once compiled, run 'make test-e2e-ui-reuse'. Stop with 'make e2e-down'."

test-e2e-ui-reuse: ## Playwright UI reusing servers started by 'make e2e-serve' (instant, no wait)
	pnpm --filter web test:e2e:ui:reuse

e2e-down: ## Stop the E2E servers started by 'make e2e-serve' and remove the test DB
	-pkill -f "serve-api.mjs"
	-pkill -f "next dev"
	-for p in 8000 3000; do \
		if command -v lsof >/dev/null 2>&1; then \
			lsof -ti :$$p 2>/dev/null | xargs -r kill -9 2>/dev/null || true; \
		else \
			for pid in $$(netstat -ano 2>/dev/null | grep ":$$p" | grep LISTENING | awk '{print $$NF}' | sort -u); do \
				taskkill //F //PID $$pid >/dev/null 2>&1 || true; \
			done; \
		fi; \
	done
	-rm -f api/database/database.e2e.sqlite api/database/database.e2e.baseline.sqlite api/database/database.e2e.sqlite-wal api/database/database.e2e.sqlite-shm
	@echo "E2E servers stopped and test database removed."

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