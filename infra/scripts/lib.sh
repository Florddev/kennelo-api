#!/usr/bin/env bash
# lib.sh — Fonctions partagées des scripts de déploiement.
# Sourcé par deploy-*.sh, ne s'exécute pas directement.

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
STACK_DIR="$REPO_ROOT/infra/stacks"
CONF_DIR="$REPO_ROOT/infra/config"

load_env() {
  if [[ "${ENV:-}" != "preprod" && "${ENV:-}" != "prod" ]]; then
    echo "Erreur : ENV doit valoir 'preprod' ou 'prod' (reçu : '${ENV:-}')." >&2
    echo "Usage : ENV=preprod $0" >&2
    exit 1
  fi

  local env_file="$CONF_DIR/$ENV.conf"
  if [[ ! -f "$env_file" ]]; then
    echo "Erreur : fichier d'environnement introuvable : $env_file" >&2
    exit 1
  fi

  set -a
  # shellcheck disable=SC1090
  source "$env_file"
  set +a
}

require_vars() {
  local var missing=0
  for var in "$@"; do
    if [[ -z "${!var:-}" ]]; then
      echo "Erreur : variable requise non définie : $var (voir infra/config/example.conf)" >&2
      missing=1
    fi
  done
  if [[ $missing -ne 0 ]]; then
    exit 1
  fi
}
