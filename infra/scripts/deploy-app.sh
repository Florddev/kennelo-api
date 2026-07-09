#!/usr/bin/env bash
# deploy-app.sh — Déploie les stacks applicatives (api, web, reverb).
# Appelé par le CD à chaque merge (préprod) ou release taguée (prod),
# ou à la main. Le checkout git est la responsabilité de l'appelant.
#
# Usage :
#   ENV=preprod ./infra/scripts/deploy-app.sh
#   ENV=prod IMAGE_TAG=v0.2.0 ./infra/scripts/deploy-app.sh
set -euo pipefail

source "$(cd "$(dirname "$0")" && pwd)/lib.sh"

load_env
require_vars IMAGE_TAG APP_URL FRONTEND_URL CORS_ALLOWED_ORIGINS \
  DOMAIN_LOCALES AWS_URL REVERB_PUBLIC_HOST \
  API_REPLICAS WEB_REPLICAS REVERB_REPLICAS

echo "==> Déploiement applicatif ($ENV, images :$IMAGE_TAG)"
docker stack deploy -c "$STACK_DIR/api.yml"    api    --with-registry-auth
docker stack deploy -c "$STACK_DIR/web.yml"    web    --with-registry-auth
docker stack deploy -c "$STACK_DIR/reverb.yml" reverb --with-registry-auth

echo ""
echo "Déploiement applicatif terminé."
