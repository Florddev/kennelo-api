#!/usr/bin/env bash
# restore.sh — Restauration Kennelo depuis n'importe laquelle des 3 copies.
# ATTENTION : écrase la base de données cible. Confirmation exigée sans --yes.
#
# Usage :
#   restore.sh --source local|spaces|r2 [--latest | --file db/daily/db-....dump.gpg]
#              [--db-only | --media-only] [--yes]
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/backups}"
BACKUP_BUCKET="${BACKUP_BUCKET:-kennelo-backups}"
DB_HOST="${DB_HOST:-postgres}"
DB_USER="${DB_USER:-kennelo}"
DB_NAME="${DB_NAME:-kennelo}"
MEDIA_BUCKET="${MEDIA_BUCKET:-kennelo-media}"

PG_PASSWORD_FILE="${PG_PASSWORD_FILE:-/run/secrets/kennelo_postgres_password}"
GPG_PASSPHRASE_FILE="${GPG_PASSPHRASE_FILE:-/run/secrets/kennelo_backup_gpg_passphrase}"
RCLONE_CONF="${RCLONE_CONF:-/run/secrets/kennelo_backup_rclone_conf}"
MINIO_USER_FILE="${MINIO_USER_FILE:-/run/secrets/kennelo_minio_root_user}"
MINIO_PASSWORD_FILE="${MINIO_PASSWORD_FILE:-/run/secrets/kennelo_minio_root_password}"
MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"

SOURCE="local"
FILE=""
LATEST=0
SCOPE="all"
ASSUME_YES=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --source) SOURCE="$2"; shift 2 ;;
    --file) FILE="$2"; shift 2 ;;
    --latest) LATEST=1; shift ;;
    --db-only) SCOPE="db"; shift ;;
    --media-only) SCOPE="media"; shift ;;
    --yes) ASSUME_YES=1; shift ;;
    *) echo "Argument inconnu : $1" >&2; exit 1 ;;
  esac
done

rclone_cmd() {
  rclone --config "$RCLONE_CONF" "$@"
}

export RCLONE_CONFIG_MINIO_TYPE=s3
export RCLONE_CONFIG_MINIO_PROVIDER=Minio
export RCLONE_CONFIG_MINIO_ENDPOINT="$MINIO_ENDPOINT"
export RCLONE_CONFIG_MINIO_ACCESS_KEY_ID="$(cat "$MINIO_USER_FILE")"
export RCLONE_CONFIG_MINIO_SECRET_ACCESS_KEY="$(cat "$MINIO_PASSWORD_FILE")"

if [[ "$SCOPE" != "media" ]]; then
  if [[ "$LATEST" == "1" ]]; then
    if [[ "$SOURCE" == "local" ]]; then
      FILE="db/daily/$(ls -1 "$BACKUP_DIR/db/daily" | sort | tail -1)"
    else
      FILE="db/daily/$(rclone_cmd lsf --files-only "${SOURCE}:${BACKUP_BUCKET}/db/daily" | sort | tail -1)"
    fi
  fi
  if [[ -z "$FILE" ]]; then
    echo "Erreur : préciser --latest ou --file db/daily/<nom>.dump.gpg" >&2
    exit 1
  fi
fi

echo "==> Restauration Kennelo"
echo "    source : $SOURCE"
[[ "$SCOPE" != "media" ]] && echo "    dump   : $FILE"
echo "    cible  : base ${DB_NAME}@${DB_HOST} et/ou bucket ${MEDIA_BUCKET} — ÉCRASEMENT"
if [[ "$ASSUME_YES" != "1" ]]; then
  read -r -p "    Taper 'restore' pour confirmer : " answer
  [[ "$answer" == "restore" ]] || { echo "Abandon."; exit 1; }
fi

if [[ "$SCOPE" != "media" ]]; then
  workdir="$(mktemp -d)"
  if [[ "$SOURCE" == "local" ]]; then
    cp "$BACKUP_DIR/$FILE" "$workdir/dump.gpg"
  else
    rclone_cmd copyto "${SOURCE}:${BACKUP_BUCKET}/$FILE" "$workdir/dump.gpg"
  fi

  echo "==> Déchiffrement et restauration PostgreSQL"
  gpg --batch --yes --decrypt --passphrase-file "$GPG_PASSPHRASE_FILE" \
    -o "$workdir/dump.pgcustom" "$workdir/dump.gpg"
  PGPASSWORD="$(cat "$PG_PASSWORD_FILE")" pg_restore \
    --clean --if-exists --no-owner \
    -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" "$workdir/dump.pgcustom"
  rm -rf "$workdir"
fi

if [[ "$SCOPE" != "db" ]]; then
  echo "==> Restauration des médias vers minio:${MEDIA_BUCKET}"
  if [[ "$SOURCE" == "local" ]]; then
    rclone_cmd copy "$BACKUP_DIR/media" "minio:${MEDIA_BUCKET}"
  else
    rclone_cmd copy "${SOURCE}:${BACKUP_BUCKET}/media" "minio:${MEDIA_BUCKET}"
  fi
fi

echo "==> Restauration terminée ($(date -u +%FT%TZ))"
