#!/usr/bin/env bash
# deploy.sh — Orchestrateur de déploiement complet (setup initial, debug).
# Enchaîne plateforme, applicatif, puis admin (prod uniquement).
# Le CD n'appelle PAS ce script : il appelle deploy-app.sh directement.
# Le checkout git est la responsabilité de l'appelant.
#
# Usage :
#   ENV=preprod ./infra/scripts/deploy.sh
#   ENV=prod IMAGE_TAG=v0.2.0 ./infra/scripts/deploy.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

source "$SCRIPT_DIR/lib.sh"
load_env

"$SCRIPT_DIR/deploy-platform.sh"

echo ""
"$SCRIPT_DIR/deploy-app.sh"

if [[ "$ENV" == "prod" ]]; then
  echo ""
  "$SCRIPT_DIR/deploy-admin.sh"
fi

echo ""
echo "==> État des services"
docker service ls

echo ""
echo "Déploiement complet terminé ($ENV)."
