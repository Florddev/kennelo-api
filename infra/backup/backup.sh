#!/usr/bin/env bash
# backup.sh — Sauvegarde 3-2-1 de Kennelo.
# Copie 1 : volume local (/backups). Copie 2 et 3 : remotes rclone définis
# dans le secret kennelo_backup_rclone_conf (spaces, r2 par défaut).
# Périmètre : dump PostgreSQL chiffré + fichiers utilisateurs MinIO.
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/backups}"
BACKUP_BUCKET="${BACKUP_BUCKET:-kennelo-backups}"
BACKUP_REMOTES="${BACKUP_REMOTES:-spaces r2}"
DAILY_RETENTION_DAYS="${BACKUP_DAILY_RETENTION_DAYS:-7}"
WEEKLY_RETENTION_DAYS="${BACKUP_WEEKLY_RETENTION_DAYS:-28}"

DB_HOST="${DB_HOST:-postgres}"
DB_USER="${DB_USER:-kennelo}"
DB_NAME="${DB_NAME:-kennelo}"
MEDIA_BUCKET="${MEDIA_BUCKET:-kennelo-media}"

PG_PASSWORD_FILE="${PG_PASSWORD_FILE:-/run/secrets/kennelo_postgres_password}"
GPG_PASSPHRASE_FILE="${GPG_PASSPHRASE_FILE:-/run/secrets/kennelo_backup_gpg_passphrase}"
RCLONE_CONF="${RCLONE_CONF:-/run/secrets/kennelo_backup_rclone_conf}"
HEALTHCHECKS_URL_FILE="${HEALTHCHECKS_URL_FILE:-/run/secrets/kennelo_backup_healthchecks_url}"
MINIO_USER_FILE="${MINIO_USER_FILE:-/run/secrets/kennelo_minio_root_user}"
MINIO_PASSWORD_FILE="${MINIO_PASSWORD_FILE:-/run/secrets/kennelo_minio_root_password}"
MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"

hc_url=""
if [[ -f "$HEALTHCHECKS_URL_FILE" ]]; then
  hc_url="$(cat "$HEALTHCHECKS_URL_FILE")"
fi

ping_hc() {
  local suffix="${1:-}"
  if [[ -n "$hc_url" ]]; then
    curl -fsS -m 10 --retry 3 "${hc_url}${suffix}" >/dev/null || true
  fi
}

on_error() {
  echo "!! Échec de la sauvegarde ($(date -u +%FT%TZ))"
  rm -f "$BACKUP_DIR/db/daily/${dump_name:-}.part" 2>/dev/null || true
  ping_hc "/fail"
}
trap on_error ERR

rclone_cmd() {
  rclone --config "$RCLONE_CONF" "$@"
}

export RCLONE_CONFIG_MINIO_TYPE=s3
export RCLONE_CONFIG_MINIO_PROVIDER=Minio
export RCLONE_CONFIG_MINIO_ENDPOINT="$MINIO_ENDPOINT"
export RCLONE_CONFIG_MINIO_ACCESS_KEY_ID="$(cat "$MINIO_USER_FILE")"
export RCLONE_CONFIG_MINIO_SECRET_ACCESS_KEY="$(cat "$MINIO_PASSWORD_FILE")"

stamp="$(date -u +%Y-%m-%dT%H%M%SZ)"
dump_name="db-${stamp}.dump.gpg"

echo "==> Sauvegarde Kennelo ${stamp}"
mkdir -p "$BACKUP_DIR/db/daily" "$BACKUP_DIR/db/weekly" "$BACKUP_DIR/media"

echo "==> Dump PostgreSQL (${DB_NAME}@${DB_HOST}) chiffré"
PGPASSWORD="$(cat "$PG_PASSWORD_FILE")" pg_dump -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -Fc \
  | gpg --batch --yes --symmetric --cipher-algo AES256 \
      --passphrase-file "$GPG_PASSPHRASE_FILE" \
      -o "$BACKUP_DIR/db/daily/${dump_name}.part"
mv "$BACKUP_DIR/db/daily/${dump_name}.part" "$BACKUP_DIR/db/daily/$dump_name"

if [[ "$(date -u +%u)" == "7" ]]; then
  echo "==> Dimanche : copie hebdomadaire"
  cp "$BACKUP_DIR/db/daily/$dump_name" "$BACKUP_DIR/db/weekly/$dump_name"
fi

echo "==> Copie locale des médias MinIO (${MEDIA_BUCKET})"
rclone_cmd copy "minio:${MEDIA_BUCKET}" "$BACKUP_DIR/media"

for remote in $BACKUP_REMOTES; do
  echo "==> Envoi vers ${remote}:${BACKUP_BUCKET}"
  rclone_cmd copy "$BACKUP_DIR/db" "${remote}:${BACKUP_BUCKET}/db"
  rclone_cmd copy "$BACKUP_DIR/media" "${remote}:${BACKUP_BUCKET}/media"
done

echo "==> Purge (quotidiens > ${DAILY_RETENTION_DAYS}j, hebdomadaires > ${WEEKLY_RETENTION_DAYS}j)"
find "$BACKUP_DIR/db/daily" -name '*.dump.gpg' -mtime +"$DAILY_RETENTION_DAYS" -delete
find "$BACKUP_DIR/db/weekly" -name '*.dump.gpg' -mtime +"$WEEKLY_RETENTION_DAYS" -delete
for remote in $BACKUP_REMOTES; do
  rclone_cmd mkdir "${remote}:${BACKUP_BUCKET}/db/daily"
  rclone_cmd mkdir "${remote}:${BACKUP_BUCKET}/db/weekly"
  rclone_cmd delete --min-age "${DAILY_RETENTION_DAYS}d" "${remote}:${BACKUP_BUCKET}/db/daily" || true
  rclone_cmd delete --min-age "${WEEKLY_RETENTION_DAYS}d" "${remote}:${BACKUP_BUCKET}/db/weekly" || true
done

ping_hc
echo "==> Sauvegarde terminée ($(date -u +%FT%TZ))"
