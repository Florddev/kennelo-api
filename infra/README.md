# Infrastructure Kennelo — Docker Swarm

Infrastructure as Code des environnements Kennelo. Les stacks, scripts et
fichiers de configuration de ce dossier servent tous les environnements : la
production aujourd'hui, la préproduction dès que son VPS sera monté. Ce qui
varie entre environnements ne vit jamais dans les stacks, uniquement dans
`infra/env/`.

## Architecture

### Production

Cluster Swarm 3 nœuds sur DigitalOcean (Frankfurt FRA1) :

- **swarm-manager** (4 GB) : orchestration + services stateful (PostgreSQL,
  Redis, MinIO) + reverse proxy (Nginx Proxy Manager) + monitoring
- **swarm-worker-1** (2 GB) : services applicatifs répliqués
- **swarm-worker-2** (2 GB) : services applicatifs répliqués

Domaines publics : `kennelo.fr`, `api.kennelo.fr`, `ws.kennelo.fr`,
`admin.kennelo.fr` (back-office). Le sous-domaine `cdn.kennelo.fr` est prévu
pour servir les médias MinIO ; son proxy sera branché au chantier 2.

### Préproduction

VPS dédié Hetzner CX23 (Falkenstein), Swarm mono-nœud (le manager est aussi
le worker), isolation complète : aucune ressource partagée avec la
production, ni base, ni Redis, ni MinIO, ni reverse proxy. Mêmes stacks que
la production, déployées avec `infra/env/preprod.env` (replicas 1, domaines
`preprod.kennelo.fr`, `api.preprod.kennelo.fr`, `ws.preprod.kennelo.fr`,
`cdn.preprod.kennelo.fr`, `admin.preprod.kennelo.fr`), sans les stacks
d'administration. Alimentée en continu : chaque merge sur main y est
déployé automatiquement par le workflow deploy-preprod.

## Réseaux overlay

- `kennelo-public` : services exposés au web (NPM, web, api, reverb)
- `kennelo-internal` : services backend privés (PostgreSQL, Redis, MinIO),
  jamais exposés directement

Créés une fois par environnement, via `bootstrap.sh` :

```bash
docker network create --driver overlay --attachable kennelo-public
docker network create --driver overlay --attachable kennelo-internal
```

## Secrets

Tous les mots de passe sont gérés via Docker Secrets (chiffrés dans le Raft
log, montés en tmpfs). Aucun secret n'apparaît dans les fichiers de ce
dossier. Ils sont créés une fois par environnement via
`infra/scripts/bootstrap.sh` ; chaque environnement porte les mêmes noms de
secrets (`kennelo_*`) mais des valeurs différentes.

## Fichiers de configuration (`infra/env/`)

Les valeurs qui varient entre environnements — tag d'image, domaines publics,
CORS, replicas, identifiants publics du client OAuth Google
(`NEXT_PUBLIC_GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_ID`, `GOOGLE_REDIRECT_URI`) —
vivent dans des fichiers versionnés, sourcés par les scripts de déploiement
puis interpolés dans les stacks via `${VAR}` :

- `prod.env` : valeurs de la production
- `preprod.env` : valeurs de la préproduction
- `example.env` : template documenté, liste chaque variable attendue avec son
  commentaire

Règle d'or : **aucun secret dans ces fichiers**. Mots de passe, clés API,
tokens et certificats vont exclusivement dans les Docker Secrets.

Discipline : toute nouvelle variable ajoutée à une stack doit aussi être
ajoutée à `example.env`, avec son commentaire. C'est ce qui garde le template
fiable comme documentation vivante.

`diff infra/env/prod.env infra/env/preprod.env` montre en une commande toutes
les différences de configuration entre les deux environnements.

## Déploiement

Les scripts sont découpés par cycle de vie des services :

| Script               | Stacks                                | Quand                                                                        | Environnements  |
| -------------------- | ------------------------------------- | ---------------------------------------------------------------------------- | --------------- |
| `deploy-app.sh`      | api, web, reverb, back-office, worker | À chaque merge (préprod) ou release (prod) ; le seul script appelé par le CD | preprod et prod |
| `deploy-platform.sh` | proxy, postgres, redis, minio         | Setup initial, changement de config plateforme                               | preprod et prod |
| `deploy-admin.sh`    | portainer, monitoring                 | Setup initial, upgrade des outils d'admin                                    | prod uniquement |
| `deploy.sh`          | orchestrateur des trois               | Setup complet d'un environnement, debug                                      | preprod et prod |

Tous s'exécutent sur le manager Swarm de l'environnement visé, depuis la
racine du dépôt cloné, et lisent deux variables :

- `ENV` (obligatoire) : `preprod` ou `prod`. Détermine le fichier
  `infra/env/$ENV.env` sourcé avant le déploiement.
- `IMAGE_TAG` (optionnel) : surcharge le tag d'image par défaut de
  l'environnement (`preprod-latest` en préprod, `prod` en prod), par exemple
  pour déployer une version précise.

```bash
ENV=preprod ./infra/scripts/deploy-app.sh
ENV=prod IMAGE_TAG=v0.1.0 ./infra/scripts/deploy-app.sh
```

Les scripts ne touchent pas à git : le checkout du bon ref (main en préprod,
tag de release en prod) est la responsabilité de l'appelant — humain via le
Makefile, ou workflow de CD. Prérequis : le manager doit être authentifié sur
GHCR (`docker login ghcr.io`) pour résoudre les tags d'images au déploiement.

## Makefile

Le Makefile à la racine du dépôt compose le checkout git et l'appel du bon
script pour l'opérateur humain. Les cibles de déploiement s'exécutent sur le
manager Swarm de l'environnement visé (pas sur un poste de développement) :

```bash
make deploy-preprod           # checkout main + pull + déploiement applicatif préprod
make deploy-prod TAG=v0.2.0   # fetch tags + checkout du tag + déploiement prod épinglé
make rollback TAG=v0.1.0      # redéploiement d'une release antérieure
make status                   # état des services applicatifs (api, web, reverb, back-office, worker)
```

`TAG` est obligatoire et validé strictement (format `vX.Y.Z`) avant toute
commande git ou docker. Après `deploy-prod` ou `rollback`, le manager reste
sur le tag déployé (HEAD détaché) : l'état du dépôt reflète ce qui tourne.

## Procédure de rollback

Un rollback est le redéploiement d'un tag antérieur — pas de mécanisme dédié,
les images `:vX.Y.Z` étant immuables sur GHCR :

```bash
git tag -l --sort=-v:refname   # lister les releases, la plus récente en premier
make rollback TAG=v0.1.0
```

Pour un rollback plus fin qu'une release (revenir à un commit précis de
main), les images `:sha-xxx` publiées à chaque build restent disponibles :

```bash
ENV=prod IMAGE_TAG=sha-abc1234 ./infra/scripts/deploy-app.sh
```

Le cycle de release complet (quand taguer, discipline de validation en
préproduction) est documenté dans `infra/docs/observability.md`, section
« Déploiement et environnements ».

## Stacks

| Fichier         | Service(s)                                   | Réseau            | Cycle de vie |
| --------------- | -------------------------------------------- | ----------------- | ------------ |
| proxy.yml       | Nginx Proxy Manager                          | public            | plateforme   |
| postgres.yml    | PostgreSQL 16                                | internal          | plateforme   |
| redis.yml       | Redis 7                                      | internal          | plateforme   |
| minio.yml       | MinIO                                        | internal + public | plateforme   |
| api.yml         | API Laravel                                  | internal + public | applicatif   |
| web.yml         | Front Next.js                                | public            | applicatif   |
| reverb.yml      | Laravel Reverb (WebSocket)                   | internal + public | applicatif   |
| worker.yml      | Workers de queue Laravel (queue:work)        | internal          | applicatif   |
| back-office.yml | Back-office d'administration (Next.js)       | public            | applicatif   |
| portainer.yml   | Portainer + agents                           | agent_network     | admin (prod) |
| monitoring.yml  | Prometheus, Grafana, node-exporter, cAdvisor | monitoring        | admin (prod) |
| backup.yml      | Sauvegardes 3-2-1 (pg_dump + médias MinIO)   | internal          | admin (prod) |

## Versions épinglées

- Docker Engine : 28.5.1 (apt-mark hold) — évite les bugs Swarm de Docker 29
- PostgreSQL : 16-alpine
