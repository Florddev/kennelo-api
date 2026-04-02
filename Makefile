.PHONY: help main update start larastan

help: ## Show this message
	@echo "Available commands:"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z_-]+:.*##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

main: ## Checkout main branch && git pull
	git checkout main && git pull

update: ## Update/install dependencies Back/Front
	if [ -f api/composer.lock ]; then cd api && composer update; else cd api && composer install; fi
	cd api && php artisan storage:link
	pnpm install

start: ## Start API and Web
	cd api && php -d upload_max_filesize=8M -d post_max_size=12M artisan serve &
	cd api && php artisan queue:work --queue=default --tries=1 --memory=1024 --timeout=180 &
	pnpm dev &

larastan: ## Run larastan
	cd api && ./vendor/bin/phpstan analyse --memory-limit=2G

setup:
	cd api && cp .env.example .env
	cd api && composer install
	cd api && php artisan key:generate
	cd api && make refresh
	make update