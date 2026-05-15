# Semaine 3 — Dockerfiles de production + CI/CD GHCR

## Contexte

Troisième étape de la migration vers Docker Swarm. Après PostgreSQL en Semaine 1
et Redis + MinIO en Semaine 2, il faut maintenant **packager les applications**
pour qu'elles puissent tourner dans le cluster. Concrètement : deux images
Docker (API Laravel et Web Next.js) optimisées pour la production, et un
pipeline CI/CD qui publie automatiquement ces images sur un registry à chaque
push.

L'objectif est d'arriver à un état où, à partir d'un simple `git push`, on
obtient deux images prêtes à être déployées sur n'importe quel cluster Swarm.

## Objectif

- Dockerfile de production pour le Web Next.js (monorepo pnpm + standalone)
- Dockerfile de production pour l'API Laravel (PHP 8.4 + nginx + supervisord)
- Workflow GitHub Actions qui build et push sur GitHub Container Registry
- Validation end-to-end : pull des images depuis GHCR et test d'exécution

## Durée effective

~3 heures sur 1 session (S3.1 à S3.7).

## Réalisations

### 1. Préparation Next.js — output standalone

Modification de `apps/web/next.config.mjs` pour ajouter un mode `docker` qui
active le mode de build standalone de Next.js. Trois modes coexistent
désormais :

- `mobile` → `output: 'export'` (statique pour Capacitor)
- `docker` → `output: 'standalone'` (serveur Node autonome)
- aucun → SSR classique (dev local)

Le `outputFileTracingRoot` est fixé à la racine du monorepo (`../../`) pour
que Next.js inclue correctement les workspace packages (`@workspace/ui`,
`@workspace/common`, `@workspace/modules`, `@workspace/translations`).

**Découverte importante** : le build standalone échoue si le package
`@workspace/translations` n'a pas été pré-buildé. La sortie `dist/` (avec les
JSON ar/en/fr) doit exister avant que Next.js compile. C'est documenté dans
le Dockerfile (`pnpm --filter @workspace/translations build` avant
`pnpm --filter web build`).

Validation : `NEXT_PUBLIC_PLATFORM=docker pnpm build` produit un
`.next/standalone/apps/web/server.js` qui sert l'app sur le port 3000 avec
middleware i18n et multi-domaine fonctionnels.

Commit : `feat: add 'docker' platform target to next.config (standalone output)`

### 2. Dockerfile de production Next.js

Multi-stage en trois étapes :

1. **deps** (`node:22-alpine`) : installe pnpm 10.4.1, copie les
   `package.json` individuels des workspaces (pour maximiser le cache des
   layers), puis `pnpm install --frozen-lockfile`.
2. **builder** : copie les `node_modules` du stage deps + les packages
   workspace, copie le code source, build `@workspace/translations` puis
   `web` en mode standalone.
3. **runner** : image finale ultra-minimale, user non-root `nextjs:1001`,
   ne contient que `.next/standalone`, `.next/static`, `public` et un
   healthcheck via `wget`.

Un `apps/web/.dockerignore` exclut `node_modules`, `.next`, les projets natifs
Capacitor (`ios/`, `android/`) et les artefacts de build.

**Image finale : 234 MB**.

Validation : `docker run --rm -p 3001:3000 kennelo-web:dev` démarre le
serveur en 0 ms, sert la vraie home page Kennelo avec multi-domaine et
i18n parfaitement fonctionnels.

Commit : `feat: add production Dockerfile for Next.js web app`

### 3. Dockerfile de production Laravel

Multi-stage en trois étapes aussi, mais avec une complexité supérieure liée
à l'écosystème PHP :

1. **deps** (`php:8.4-fpm-alpine`) : install des libs système (libpng,
   libjpeg, libwebp, freetype, libzip, icu, oniguruma, libxml2,
   libsodium, libpq), compilation des extensions PHP (pdo_pgsql, pgsql,
   gd, intl, bcmath, exif, zip, opcache, sodium), Composer 2 copié depuis
   l'image officielle, puis `composer install --no-dev --no-scripts`.
2. **builder** : copie le code, génère l'autoloader optimisé en mode
   `classmap-authoritative`.
3. **runner** : image finale qui réinstalle les libs runtime (sans `-dev`),
   copie les extensions PHP pré-compilées depuis `deps`, met en place
   nginx + supervisord, ajoute un healthcheck sur `/up`.

Quatre fichiers de support sous `api/docker/` :

- `nginx.conf` : proxy nginx → php-fpm via unix socket, `client_max_body_size 20M`
- `php-fpm.conf` : pm dynamic, `clear_env=no` pour récupérer les env runtime
- `supervisord.conf` : orchestre nginx + php-fpm comme PID 1
- `entrypoint.sh` : cache `config`, `route`, `view` au démarrage, puis exec CMD

**Image finale : 307 MB**.

### 4. Trois bugs rencontrés et corrigés

#### Bug A — Le package translations n'est pas pré-buildé

Symptôme : `Module not found: Can't resolve '@workspace/translations/dist/ar.json'`.
Cause : Turbopack (Next.js 16) impose une résolution stricte des modules ;
le `dist/` du package n'existe que si on lance `pnpm --filter
@workspace/translations build` avant le build web. Solution : ajouter cette
étape explicitement dans le Dockerfile.

#### Bug B — Extension PHP sodium ne se compile pas

Symptôme : `configure: error: Package requirements (libsodium >= 1.0.8)
were not met`. Cause : la lib système `libsodium-dev` manquait dans le
`apk add` du stage deps. Solution : ajouter `libsodium-dev` au stage deps et
`libsodium` au stage runner (pattern multi-stage Alpine : `-dev` pour
compiler, sans `-dev` pour runtime).

#### Bug C — Conflit de version PHP

Symptôme : `symfony/filesystem v8.0.11 requires php >=8.4 -> your php
version (8.3.31) does not satisfy that requirement`. Cause : le
`composer.lock` lockait une version qui exige PHP 8.4 (résolue localement
sur le Mac dev qui tourne en PHP 8.4.8), mais l'image Docker initiale
utilisait `php:8.3-fpm-alpine`. Solution : aligner l'image sur `php:8.4-fpm-alpine`,
ce qui correspond au runtime local et à ce qu'on utilisera en prod.

Pour information : pinner la version mineure (`php:8.4.X-fpm-alpine`) dans
les futurs Dockerfiles est une amélioration future à envisager pour figer
complètement le runtime.

### 5. Validation end-to-end du Dockerfile Laravel

```bash
docker run --rm -p 8081:80 --name kennelo-api-test \
  -e APP_KEY=base64:... \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/tmp/db.sqlite \
  ...
  kennelo-api:dev
```

- Supervisord lance proprement nginx + php-fpm
- L'entrypoint cache config, routes, vues au démarrage (~1 s)
- `GET /up` retourne 200 OK en 16 ms avec la page Laravel "Application up"
- Docker healthcheck reporte le conteneur comme `healthy`
- PHP 8.4.21 confirmé via le header `X-Powered-By`

Commit : `feat: add production Dockerfile for Laravel API`

### 6. Workflow GitHub Actions pour GHCR

Création de `.github/workflows/docker-images-ci.yml`. Deux jobs en parallèle
(`build-api` et `build-web`), déclenchés sur `push` vers `main` ou
`feature/infra/swarm-cluster` (avec un `paths:` filter pour éviter de
re-builder inutilement) et exposés en `workflow_dispatch` pour pouvoir
relancer manuellement.

Chaque job :

1. Checkout du code
2. Setup Docker Buildx
3. Login sur `ghcr.io` avec le `GITHUB_TOKEN` automatique
4. Calcul des tags via `docker/metadata-action` :
    - `<branch>` (ex. `feature-infra-swarm-cluster`)
    - `sha-<commit_sha>` (ex. `sha-d1f7f90`)
    - `latest` (uniquement sur la branche par défaut)
5. Build & push avec cache GitHub Actions (`type=gha`, scope par image)

Détail important : GHCR exige des noms en lowercase. `Anthony14FR` doit
devenir `anthony14fr` dans les références d'image. C'est hardcodé dans
l'env `IMAGE_OWNER`.

Commit : `feat: add GitHub Actions workflow for GHCR image publishing`

### 7. Validation du pipeline complet

Premier run du workflow sur le push de `feature/infra/swarm-cluster` :

- `Build API image` → succès en **4 min 54 s**
- `Build Web image` → succès en parallèle
- Les deux images apparaissent sur https://github.com/Anthony14FR?tab=packages

Pull en local pour vérification :

```bash
docker login ghcr.io -u ThamiEngineering   # avec un PAT GitHub scopé read:packages
docker pull --platform linux/amd64 ghcr.io/anthony14fr/kennelo-api:feature-infra-swarm-cluster
docker pull --platform linux/amd64 ghcr.io/anthony14fr/kennelo-web:feature-infra-swarm-cluster
```

Les images CI sont en amd64 (le runner GitHub Actions est x86_64), tandis
que les builds locaux sur Mac M1 sont en arm64. L'option `--platform
linux/amd64` force la compatibilité via Rosetta. Pour la production sur
DigitalOcean (VPS x86_64), c'est parfait sans modification.

**Trade-off conscient** : on builde uniquement en amd64 pour ne pas doubler
le temps CI. Si le besoin de tester en natif sur Mac M1 devient critique,
on pourra activer un build multi-arch via `docker/build-push-action`
plus tard.

### 8. Authentification GHCR

Premier login en local échoué par confusion entre Docker Hub et GHCR (le
prompt interactif `Username:` est par défaut sur Docker Hub). Solution :
`docker logout ghcr.io` puis `docker login ghcr.io -u <user>` explicitement,
en utilisant un Personal Access Token GitHub avec le scope `read:packages`
uniquement (principe du moindre privilège).

Le token est stocké dans `~/.docker/config.json` et n'a pas besoin d'être
re-saisi à chaque pull. Il expire dans 90 jours (réglage de sécurité
raisonnable).

## Validation

À la fin de la semaine :

- Deux Dockerfiles de production validés en local (`kennelo-web:dev` 234 MB,
  `kennelo-api:dev` 307 MB), avec tests d'exécution réels
- Workflow GHCR fonctionnel, deux images pushées sur push (4-5 min par image)
- Les images sont pullables et identiques à ce qu'on builde en local (mêmes
  layers, même architecture après alignement)
- 0 vulnérabilité ajoutée (Dependabot toujours à 45 sur main)
- Pipeline CI/CD complet : `git push` → image production prête à déployer

## Récap des commits

- `feat: add 'docker' platform target to next.config (standalone output)`
- `feat: add production Dockerfile for Next.js web app`
- `feat: add production Dockerfile for Laravel API`
- `feat: add GitHub Actions workflow for GHCR image publishing`
- `feat: add week 3 journal — production Dockerfiles and GHCR` (ce commit)

## Prochaine étape (Semaine 4)

- Provisioning de 3 VPS sur DigitalOcean (Frankfurt, plan basic 4GB/2GB/2GB)
- Configuration SSH (clés + sudoers) sur chaque nœud
- Init Docker Swarm sur le manager, join des deux workers
- Premier déploiement test : pull `kennelo-api:latest` depuis GHCR et run sur
  le cluster comme un service Swarm
- DNS Cloudflare + Nginx Proxy Manager pour la terminaison TLS
- Récupération des images GHCR depuis le cluster via un PAT GitHub partagé
