# Semaine 2 — Redis (cache, queue, sessions) + MinIO (stockage S3)

## Contexte

Deuxième étape de la migration vers Docker Swarm. Après PostgreSQL en Semaine 1,
il faut maintenant préparer toutes les ressources partagées entre les replicas
API : le cache (impossible en mémoire ou en BDD sur un cluster), la queue de jobs
(qui doit être centralisée), les sessions utilisateurs (qui doivent survivre au
hash routing), et le stockage des fichiers uploadés (qui doit être accessible
depuis n'importe quel replica).

Trois nouveaux services Docker en local, deux nouvelles dépendances Composer,
plusieurs fichiers de config Laravel à adapter.

## Objectif

Faire tourner Kennelo en local avec Redis pour cache + queue + sessions, et
MinIO comme stockage S3-compatible pour les médias gérés par Spatie MediaLibrary.

## Durée effective

~3 heures sur 1 session.

## Réalisations

### 1. Ajout de Redis 7 au docker-compose.dev.yml

Service Redis 7 Alpine avec authentification par mot de passe, volume nommé pour
la persistance entre restarts, et healthcheck via `redis-cli ping`.

Commit : `feat: add Redis 7 to docker-compose.dev.yml`

### 2. Installation de Predis (client PHP pur)

Laravel propose deux clients Redis : `phpredis` (extension C native, plus rapide)
et `predis` (paquet Composer en PHP pur). Choix de Predis pour cette étape parce
que :

- Pas besoin d'installer une extension PHP système (sur Mac M1 + PHP 8.4 via
  Homebrew, `pecl install redis` peut être capricieux)
- Pas de couche supplémentaire à gérer dans les futurs Dockerfiles de prod
- Performances suffisantes pour les ~100 utilisateurs de Kennelo
- Possibilité de basculer sur phpredis plus tard en changeant une seule variable
  d'environnement (`REDIS_CLIENT=phpredis`)

Commit : `feat: add predis/predis to use Redis without PHP extension`

### 3. Bascule de cache / queue / sessions vers Redis

Modifications dans `api/.env` et `api/.env.example` :

- `CACHE_STORE` : `database` → `redis` (utilise la base Redis #1 via
  `REDIS_CACHE_DB=1` pour isolation)
- `QUEUE_CONNECTION` : `database` → `redis`
- `SESSION_DRIVER` : `database` → `redis`
- `REDIS_CLIENT` : `phpredis` → `predis`
- `REDIS_PASSWORD` : aligné avec le `docker-compose.dev.yml`
- Ajout explicite de `REDIS_DB=0` et `REDIS_CACHE_DB=1`

Validation via Tinker :

```php
Cache::put('test-cache', 'cache works!', 60);   // true
Cache::get('test-cache');                       // "cache works!"
Redis::connection('default')->ping();           // PONG
Redis::connection('cache')->ping();             // PONG
```

Vérification directe dans Redis (CLI) : la clé apparaît bien dans la DB 1
(`kennelo-database-kennelo-cache-test-cache`), preuve de l'isolation entre
cache et sessions/queue.

**Piège rencontré** : après modification de `.env`, Laravel continuait à
utiliser l'ancienne config (cache de bootstrap). `php artisan config:clear`
résout. À retenir pour la prod sur Swarm : tout changement d'env nécessite un
redémarrage du conteneur API.

**Limitation du test queue** : `dispatch(function() { ... })` ne fonctionne pas
dans Tinker car la sérialisation de closure échoue avec `Failed to open stream:
No such file or directory`. La closure n'est pas dans un fichier mais en
`eval()'d code`. La queue sera testée en conditions réelles lors d'un upload
d'image (qui déclenche un job d'optimisation `spatie/image-optimizer`).

Commit : `feat: switch cache, queue and sessions from database to Redis`

### 4. Ajout de MinIO au docker-compose.dev.yml

Service MinIO Community Edition avec :

- Port 9000 pour l'API S3
- Port 9001 pour la console web
- Credentials `kennelo` / `kennelo_dev_password` (alignés avec le reste)
- Volume nommé pour la persistance des médias
- Healthcheck via `curl /minio/health/live`

Commit : `feat: add MinIO S3-compatible storage to docker-compose.dev.yml`

### 5. Création du bucket et configuration de l'accès public

Découverte importante : **MinIO Community Edition a perdu la gestion des access
policies via l'interface web depuis la version 2.0.0 (début 2026)**. L'UI
sert désormais uniquement de navigateur d'objets ; toute l'administration doit
passer par le CLI `mc`. Cette limitation est documentée et constitue un
argument pour automatiser la configuration en IaC (Ansible) en production.

Bucket `kennelo-media` créé via l'UI puis rendu public en lecture via :

```bash
docker exec -it kennelo-minio-dev mc alias set local http://localhost:9000 kennelo kennelo_dev_password
docker exec -it kennelo-minio-dev mc anonymous set download local/kennelo-media
```

Validation : upload manuel d'une image via l'UI MinIO, puis accès direct via
`http://localhost:9000/kennelo-media/<filename>` dans le navigateur — image
affichée sans authentification.

### 6. Branchement de Laravel sur MinIO

Installation du driver Flysystem S3 :

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Cette commande a aussi installé `aws/aws-sdk-php` et quatre autres
dépendances transitives. Side effect intéressant : la mise à jour des
dépendances a fait passer le nombre de vulnérabilités Dependabot de 72 à 45
sur la branche `main` (résolution automatique de paquets vulnérables).

Modifications dans `api/.env` et `api/.env.example` :

- `FILESYSTEM_DISK` : `local` → `s3`
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` : credentials MinIO
- `AWS_BUCKET` : `kennelo-media`
- `AWS_ENDPOINT` : `http://localhost:9000` ← **variable clé** qui fait que
  Laravel parle à MinIO au lieu des serveurs AWS
- `AWS_USE_PATH_STYLE_ENDPOINT` : `true` (format d'URL MinIO `host/bucket/file`
  au lieu de `bucket.host/file`)
- `AWS_URL` : `http://localhost:9000/kennelo-media` (préfixe utilisé par
  `Storage::url()` et `Media::getUrl()`)

Validation via Tinker :

```php
Storage::disk('s3')->put('test/hello.txt', 'Hello!');   // true
Storage::disk('s3')->get('test/hello.txt');             // "Hello!"
Storage::disk('s3')->url('test/hello.txt');
// "http://localhost:9000/kennelo-media/test/hello.txt"
```

Fichier visible dans la console MinIO et accessible en HTTP direct.

Commit : `feat: add league/flysystem-aws-s3-v3 to enable Laravel S3 disk`

### 7. Configuration de Spatie MediaLibrary

Publication de la config du package :

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
```

Bonne nouvelle : la config par défaut utilise déjà `env('MEDIA_DISK', 'public')`,
donc il suffisait d'ajouter `MEDIA_DISK=s3` dans `.env` pour basculer tous les
uploads (User, Pet, Establishment) sur MinIO.

### 8. Bug PostgreSQL #2 découvert et corrigé

Lors du premier test d'upload via Spatie (`$user->addMedia(...)->toMediaCollection('avatar')`),
erreur :

```
SQLSTATE[22P02]: Invalid text representation: 7
ERROR: invalid input syntax for type bigint:
"019e2c0d-4612-7211-894a-21d0014382f3"
```

**Diagnostic** : la migration `create_media_table.php` utilisait
`$table->morphs('model')` qui crée une colonne `model_id BIGINT`. Mais les
modèles Kennelo (`User`, `Pet`, `Establishment`) utilisent le trait `HasUuids`,
donc leurs IDs sont des UUIDs (CHAR(36)). Même schéma que le bug
`AttributeAnimalType` de la Semaine 1 : silencieusement toléré par SQLite,
rejeté à juste titre par PostgreSQL.

**Solution** : remplacé `$table->morphs('model')` par `$table->uuidMorphs('model')`
dans la migration. Puis `php artisan migrate:fresh --seed` pour recréer la table
avec le bon type.

C'est le deuxième bug PostgreSQL rencontré pendant la migration. Confirme la
valeur de cette étape : ces bugs auraient été découverts en prod sur Swarm avec
de vrais utilisateurs si on ne les avait pas trouvés en local maintenant.

Commit : `feat: configure Spatie MediaLibrary to use S3 disk and UUID models`

### 9. Validation finale end-to-end

Après le fix de migration, le scénario complet a été testé avec succès :

```php
$user = User::first();
$tempPath = tempnam(sys_get_temp_dir(), 'test') . '.jpg';
file_put_contents($tempPath, file_get_contents('https://picsum.photos/200/200'));
$media = $user->addMedia($tempPath)->toMediaCollection('avatar');
$media->getUrl();
// "http://localhost:9000/kennelo-media/44/testvvpjp4h8ac0445uyxbe.jpg"
```

L'image téléchargée depuis Picsum est passée par Laravel, le SDK AWS,
flysystem-aws-s3-v3, l'API S3 de MinIO, et finalement le bucket public. Elle
est ensuite visualisable en HTTP direct dans le navigateur via l'URL retournée
par `getUrl()`.

## Validation

À la fin de la semaine :

- 3 conteneurs Docker healthy (PostgreSQL + Redis + MinIO)
- Cache Laravel persisté dans Redis (DB 1)
- Sessions et queue configurées sur Redis (DB 0, à valider en HTTP réel)
- Upload Spatie MediaLibrary fonctionnel de bout en bout sur MinIO
- Migrations Laravel adaptées aux UUIDs (corrigeant la migration Spatie)
- 6 fichiers modifiés, 4 commits préparés

## Récap des commits

- `feat: add Redis 7 to docker-compose.dev.yml`
- `feat: add predis/predis to use Redis without PHP extension`
- `feat: switch cache, queue and sessions from database to Redis`
- `feat: add MinIO S3-compatible storage to docker-compose.dev.yml`
- `feat: add league/flysystem-aws-s3-v3 to enable Laravel S3 disk`
- `feat: configure Spatie MediaLibrary to use S3 disk and UUID models`
- `feat: add MinIO S3 credentials and MEDIA_DISK to .env.example`
- `feat: add week 2 journal — Redis and MinIO integration` (ce commit)

## Prochaine étape (Semaine 3)

- Création du `api/Dockerfile.prod` (PHP 8.3-FPM + nginx + supervisord, image
  optimisée multi-stage)
- Création du `apps/web/Dockerfile.prod` (Next.js standalone, multi-stage)
- Activation de `output: 'standalone'` dans `apps/web/next.config.mjs`
- Adaptation des workflows GitHub Actions existants pour builder et publier les
  images sur GHCR (`ghcr.io/anthony14fr/kennelo-api` et `kennelo-web`)
- Premier test de build local des deux images Docker avant push CI
