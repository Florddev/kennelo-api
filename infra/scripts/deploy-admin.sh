#!/usr/bin/env bash
# deploy-admin.sh — Déploie les stacks d'administration (portainer,
# monitoring). Prod uniquement, à lancer manuellement lors du setup
# initial ou d'un upgrade — jamais par le CD.
#
# Usage :
#   ENV=prod ./infra/scripts/deploy-admin.sh
set -euo pipefail

source "$(cd "$(dirname "$0")" && pwd)/lib.sh"

load_env

if [[ "$ENV" != "prod" ]]; then
  echo "Erreur : les stacks admin ne se déploient qu'en prod (ENV=$ENV)." >&2
  exit 1
fi

echo "==> Déploiement admin ($ENV)"
docker stack deploy -c "$STACK_DIR/portainer.yml"  portainer
docker stack deploy -c "$STACK_DIR/monitoring.yml" monitoring
docker stack deploy -c "$STACK_DIR/backup.yml"     backup --with-registry-auth

echo ""
echo "Déploiement admin terminé."
