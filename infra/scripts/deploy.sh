#!/usr/bin/env bash
# deploy.sh — Déploie (ou met à jour) toutes les stacks Kennelo sur le Swarm.
# À exécuter sur le nœud manager depuis la racine du dépôt cloné.
set -euo pipefail

STACK_DIR="infra/stacks"

echo "==> Mise à jour du dépôt"
git pull --ff-only

echo ""
echo "==> Pull des images applicatives (depuis GHCR)"
docker pull ghcr.io/anthony14fr/kennelo-api:feature-infra-swarm-cluster
docker pull ghcr.io/anthony14fr/kennelo-web:feature-infra-swarm-cluster

echo ""
echo "==> Déploiement des stacks d'infrastructure"
docker stack deploy -c "$STACK_DIR/proxy.yml"    proxy
docker stack deploy -c "$STACK_DIR/postgres.yml" postgres
docker stack deploy -c "$STACK_DIR/redis.yml"    redis
docker stack deploy -c "$STACK_DIR/minio.yml"    minio

echo ""
echo "==> Déploiement des stacks applicatives (avec auth registry)"
docker stack deploy -c "$STACK_DIR/api.yml" api --with-registry-auth
docker stack deploy -c "$STACK_DIR/web.yml" web --with-registry-auth

echo ""
echo "==> État des services"
docker service ls

echo ""
echo "Déploiement terminé."
