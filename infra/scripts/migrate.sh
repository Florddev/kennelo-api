#!/usr/bin/env bash
# migrate.sh — Lance les migrations, les seeders de référence et crée le
# bucket MinIO. À exécuter sur un nœud worker qui héberge un conteneur api.
set -euo pipefail

API_CID="$(docker ps -q -f name=api_api | head -1)"
if [ -z "$API_CID" ]; then
  echo "Aucun conteneur api_api sur ce nœud."
  echo "Exécute ce script sur le worker qui héberge l'API (docker service ps api_api)."
  exit 1
fi

echo "==> Migrations"
docker exec "$API_CID" php artisan migrate --force

echo ""
echo "==> Seeders de référence (sans données de démo, Faker absent en prod)"
docker exec "$API_CID" php artisan db:seed --class=RoleSeeder --force
docker exec "$API_CID" php artisan db:seed --class=AnimalTypeSeeder --force
docker exec "$API_CID" php artisan db:seed --class=AttributeSeeder --force
docker exec "$API_CID" php artisan db:seed --class=ReviewCriteriaSeeder --force

echo ""
echo "Migrations et seeders de référence terminés."
echo "Note : le bucket MinIO kennelo-media se crée depuis le nœud manager :"
echo "  docker exec \$(docker ps -q -f name=minio_minio) sh -c '"
echo "    mc alias set local http://localhost:9000 \"\$(cat /run/secrets/kennelo_minio_root_user)\" \"\$(cat /run/secrets/kennelo_minio_root_password)\" &&"
echo "    mc mb --ignore-existing local/kennelo-media &&"
echo "    mc anonymous set download local/kennelo-media'"
