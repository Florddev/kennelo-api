#!/usr/bin/env bash
# deploy-platform.sh — Déploie les stacks plateforme (proxy, postgres,
# redis, minio). À lancer manuellement lors du setup initial ou d'un
# changement de config plateforme — jamais par le CD.
#
# Usage :
#   ENV=preprod ./infra/scripts/deploy-platform.sh
set -euo pipefail

source "$(cd "$(dirname "$0")" && pwd)/lib.sh"

load_env

echo "==> Déploiement plateforme ($ENV)"
docker stack deploy -c "$STACK_DIR/proxy.yml"    proxy
docker stack deploy -c "$STACK_DIR/postgres.yml" postgres
docker stack deploy -c "$STACK_DIR/redis.yml"    redis
docker stack deploy -c "$STACK_DIR/minio.yml"    minio

echo ""
echo "Déploiement plateforme terminé."
