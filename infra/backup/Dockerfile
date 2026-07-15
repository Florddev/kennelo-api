# syntax=docker/dockerfile:1.7

# Image de sauvegarde Kennelo — politique 3-2-1.
# Un cron quotidien exécute backup.sh (pg_dump chiffré + synchro des médias
# MinIO vers le volume local, DO Spaces et Cloudflare R2). restore.sh permet
# la restauration depuis n'importe laquelle des trois copies.
FROM alpine:3.21

RUN apk add --no-cache bash postgresql16-client rclone gnupg curl tzdata

COPY backup.sh restore.sh /usr/local/bin/
COPY crontab /etc/crontabs/root

RUN chmod +x /usr/local/bin/backup.sh /usr/local/bin/restore.sh

CMD ["crond", "-f", "-l", "2"]
