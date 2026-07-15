#!/usr/bin/env bash
# bootstrap.sh — Initialise le cluster Swarm Kennelo (une seule fois).
# Crée les réseaux overlay et les secrets Docker.
# À exécuter sur le nœud manager, après `docker swarm init`.
set -euo pipefail

echo "==> Création des réseaux overlay"
docker network create --driver overlay --attachable kennelo-public 2>/dev/null \
  && echo "  kennelo-public créé" || echo "  kennelo-public existe déjà"
docker network create --driver overlay --attachable kennelo-internal 2>/dev/null \
  && echo "  kennelo-internal créé" || echo "  kennelo-internal existe déjà"

echo ""
echo "==> Création des secrets Docker"
create_secret() {
  local name="$1"; local value="$2"
  if docker secret inspect "$name" >/dev/null 2>&1; then
    echo "  $name existe déjà (ignoré)"
  else
    printf '%s' "$value" | docker secret create "$name" - >/dev/null
    echo "  $name créé"
  fi
}

# Mots de passe générés aléatoirement. Les valeurs ne sont jamais affichées.
create_secret kennelo_postgres_password   "$(openssl rand -base64 32 | tr -d '\n')"
create_secret kennelo_redis_password      "$(openssl rand -base64 32 | tr -d '\n')"
create_secret kennelo_minio_root_user     "kennelo_minio_admin"
create_secret kennelo_minio_root_password "$(openssl rand -base64 32 | tr -d '\n')"
create_secret kennelo_jwt_secret          "$(openssl rand -base64 64 | tr -d '\n')"
create_secret kennelo_reverb_app_secret   "$(openssl rand -base64 32 | tr -d '\n')"

echo ""
echo "==> APP_KEY (Laravel)"
echo "  Le secret kennelo_app_key doit être créé manuellement avec une clé"
echo "  générée par 'php artisan key:generate --show' :"
echo "    printf '%s' 'base64:...' | docker secret create kennelo_app_key -"

echo ""
echo "==> GOOGLE_CLIENT_SECRET (OAuth)"
echo "  Le secret kennelo_google_client_secret doit être créé manuellement avec"
echo "  la valeur du client OAuth de la Google Cloud Console :"
echo "    printf '%s' 'GOCSPX-...' | docker secret create kennelo_google_client_secret -"

echo ""
echo "==> RESEND_KEY (Emailing)"
echo "  Le secret kennelo_resend_api_key doit être créé manuellement avec la clé"
echo "  API Resend du domaine kennelo.com :"
echo "    printf '%s' 're_...' | docker secret create kennelo_resend_api_key -"

echo ""
echo "==> Sauvegardes 3-2-1 (prod uniquement)"
echo "  Trois secrets à créer manuellement avant de déployer la stack backup :"
echo "  1. La config rclone avec les remotes 'spaces' (DO Spaces) et 'r2'"
echo "     (Cloudflare R2), clés créées dans les consoles respectives"
echo "     (template : infra/backup/rclone.conf.example) :"
echo "    docker secret create kennelo_backup_rclone_conf ./rclone.conf"
echo "  2. La passphrase de chiffrement des dumps. La générer, la stocker"
echo "     AUSSI dans le gestionnaire de mots de passe de l'équipe (sans elle,"
echo "     les sauvegardes sont illisibles), puis :"
echo "    printf '%s' '<passphrase>' | docker secret create kennelo_backup_gpg_passphrase -"
echo "  3. L'URL de ping healthchecks.io :"
echo "    printf '%s' 'https://hc-ping.com/...' | docker secret create kennelo_backup_healthchecks_url -"

echo ""
echo "==> Secrets présents :"
docker secret ls --format '  {{.Name}}'

echo ""
echo "Bootstrap terminé. Étape suivante : ./deploy.sh"
