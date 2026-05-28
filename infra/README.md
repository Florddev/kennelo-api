# Infrastructure Kennelo — Docker Swarm

Infrastructure as Code du cluster Docker Swarm de production.

## Architecture

Cluster Swarm 3 nœuds sur DigitalOcean (Frankfurt FRA1) :

- **swarm-manager** (4 GB) : orchestration + services stateful (PostgreSQL,
  Redis, MinIO) + reverse proxy (Nginx Proxy Manager) + monitoring
- **swarm-worker-1** (2 GB) : services applicatifs répliqués
- **swarm-worker-2** (2 GB) : services applicatifs répliqués

## Réseaux overlay

- `kennelo-public` : services exposés au web (NPM, web, api)
- `kennelo-internal` : services backend privés (PostgreSQL, Redis, MinIO),
  jamais exposés directement

## Secrets

Tous les mots de passe sont gérés via Docker Secrets (chiffrés dans le Raft
log, montés en tmpfs). Aucun secret n'apparaît dans ces fichiers. Les secrets
sont créés une fois sur le cluster :

```bash
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_postgres_password -
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_redis_password -
echo -n "kennelo_minio_admin" | docker secret create kennelo_minio_root_user -
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_minio_root_password -
```

## Déploiement

Les réseaux overlay sont créés une fois :

```bash
docker network create --driver overlay --attachable kennelo-public
docker network create --driver overlay --attachable kennelo-internal
```

Puis chaque stack :

```bash
docker stack deploy -c infra/stacks/proxy.yml proxy
docker stack deploy -c infra/stacks/postgres.yml postgres
```

## Stacks

| Fichier      | Service             | Réseau   | Node    |
| ------------ | ------------------- | -------- | ------- |
| proxy.yml    | Nginx Proxy Manager | public   | manager |
| postgres.yml | PostgreSQL 16       | internal | manager |

## Versions épinglées

- Docker Engine : 28.5.1 (apt-mark hold) — évite les bugs Swarm de Docker 29
- PostgreSQL : 16-alpine
