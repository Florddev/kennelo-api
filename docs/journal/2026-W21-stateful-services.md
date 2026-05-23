# Semaine 5 — Services stateful sur le cluster + Infrastructure as Code

## Contexte

Deuxième semaine de la Phase 2. Le cluster Docker Swarm est en place depuis la
Semaine 4 (trois nœuds, durcis, avec Nginx Proxy Manager et HTTPS validé). Il
faut maintenant y déployer les trois services qui constituent la fondation de
Kennelo : la base de données PostgreSQL, le cache/queue Redis, et le stockage
objet MinIO. Ces trois services sont « stateful » : ils détiennent des données
qui doivent persister.

Cette semaine introduit aussi un changement de méthode important : le passage à
une véritable approche Infrastructure as Code, où toutes les définitions de
stacks vivent dans le dépôt Git plutôt que d'être créées à la main sur le
serveur.

## Objectif

- Mettre en place les secrets Docker pour ne stocker aucun mot de passe en clair
- Déployer PostgreSQL, Redis et MinIO comme stacks Swarm, avec persistance
- Adopter l'Infrastructure as Code : stacks dans le dépôt, déploiement depuis
  un clone du dépôt sur le manager
- Segmenter le réseau entre services privés et services exposables

## Durée effective

~2 heures, dans la continuité de la Semaine 4.

## Réalisations

### 1. Secrets Docker

Avant tout déploiement, quatre secrets ont été créés sur le cluster, chacun
généré aléatoirement (sauf le nom d'utilisateur MinIO) :

```bash
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_postgres_password -
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_redis_password -
echo -n "kennelo_minio_admin" | docker secret create kennelo_minio_root_user -
openssl rand -base64 32 | tr -d '\n' | docker secret create kennelo_minio_root_password -
```

Les secrets Docker sont stockés chiffrés dans le journal Raft du Swarm et
montés en tmpfs (RAM) dans les conteneurs qui en ont besoin. Aucun mot de passe
n'est jamais écrit en clair sur le disque ni dans un fichier de configuration.
Une fois créé, un secret ne peut plus être relu en clair depuis l'extérieur :
c'est précisément le but. Pour le débogage, on peut lire le secret depuis
l'intérieur du conteneur (qui y a légitimement accès), ce qui évite toute copie
en clair côté opérateur.

Ce choix (« Stratégie A », secrets partout) a été préféré à l'approche par
fichier `.env` car il correspond au standard de production et, surtout, il rend
les fichiers de stack publiables sans risque : ils ne contiennent que des
références aux secrets, jamais les valeurs.

### 2. Passage à l'Infrastructure as Code

Constat en cours de route : créer les fichiers de stack directement sur le
manager avec `nano` est commode pour prototyper, mais c'est une mauvaise
pratique. Les fichiers ne sont alors ni versionnés, ni revus, ni
reproductibles, et seraient perdus en cas de panne du manager.

Correction : création d'un dossier `infra/` dans le dépôt, contenant un
sous-dossier `stacks/` (les fichiers compose Swarm) et un `README.md`
documentant l'architecture. Tous les fichiers existants (proxy, postgres) y ont
été migrés, puis rejoints par redis et minio.

Le manager récupère ces fichiers via un clone du dépôt en lecture seule. Une
clé de déploiement dédiée (deploy key SSH ed25519, sans passphrase) a été
générée sur le manager et enregistrée sur GitHub avec un accès en lecture
seule, limité à ce seul dépôt. Le principe du moindre privilège est ainsi
respecté : même compromis, le manager ne peut que lire ce dépôt précis, et ne
peut rien pousser.

Le cycle de déploiement devient alors :

```
édition d'un .yml sur le poste de dev
  → git commit + git push
  → sur le manager : git pull
  → docker stack deploy -c infra/stacks/<service>.yml <service>
```

Tout est versionné, traçable et reproductible. L'automatisation complète de ce
cycle (déploiement continu sur push) est planifiée pour la Phase 4 : on
n'automatise que ce qui a déjà été validé manuellement.

### 3. Segmentation réseau

Deux réseaux overlay distincts ont été créés :

- `kennelo-public` : pour les services exposés au web (Nginx Proxy Manager,
  et plus tard l'API et le Web)
- `kennelo-internal` : pour les services backend privés (PostgreSQL, Redis,
  MinIO), qui ne doivent jamais être joignables directement depuis Internet

PostgreSQL et Redis sont uniquement sur le réseau interne. MinIO est sur les
deux : interne pour que l'API y accède, public pour pouvoir exposer les
sous-domaines `s3` et `cdn` via le proxy plus tard. Cette segmentation
reproduit une vraie isolation de production.

### 4. PostgreSQL

Déployé en `postgres:16-alpine` (même version que le développement local), sur
le manager, avec un volume persistant. Le mot de passe est lu via
`POSTGRES_PASSWORD_FILE` pointant vers le secret. Un healthcheck `pg_isready`
permet au Swarm de connaître l'état réel du service.

Validation : la base `kennelo` et l'utilisateur `kennelo` sont créés, et une
requête `SELECT version()` exécutée depuis le conteneur confirme PostgreSQL
16.14 opérationnel.

### 5. Redis

Déployé en `redis:7-alpine`, sur le manager, avec persistance AOF
(`--appendonly yes`) et un volume. Le mot de passe est passé au démarrage via
`requirepass` en lisant le secret au runtime (la syntaxe `$$(cat ...)` dans le
compose permet que l'expansion ait lieu dans le conteneur et non au moment du
déploiement). Le healthcheck utilise `redis-cli ping` avec authentification.

Validation : un `redis-cli ping` authentifié répond `PONG`, et la
configuration confirme que la persistance AOF est active.

### 6. MinIO

Déployé en `minio/minio` Community, sur le manager, avec un volume. Les
identifiants root sont lus via `MINIO_ROOT_USER_FILE` et
`MINIO_ROOT_PASSWORD_FILE` (supportés nativement par l'image). Le service est
connecté aux deux réseaux (interne et public). Le healthcheck utilise
`mc ready local`.

Après démarrage, le bucket `kennelo-media` a été créé via `mc` exécuté dans le
conteneur, et rendu public en lecture (`mc anonymous set download`) pour servir
les médias, exactement comme en développement local.

### 7. État final

Les quatre services tournent et sont tous rapportés « healthy » par leurs
healthchecks respectifs :

```
minio_minio        Up (healthy)   9000/tcp
redis_redis        Up (healthy)   6379/tcp
postgres_postgres  Up (healthy)   5432/tcp
proxy_npm          Up             80/443/81
```

## Validation

À la fin de la semaine :

- Quatre secrets Docker en place, aucun mot de passe en clair nulle part
- PostgreSQL, Redis et MinIO déployés, persistants, et healthy
- Bucket `kennelo-media` créé et public en lecture
- Réseau segmenté entre privé (internal) et exposable (public)
- Toutes les stacks versionnées dans `infra/stacks/` du dépôt
- Manager configuré pour déployer depuis un clone en lecture seule (deploy key)

## Décisions et points techniques

- **Secrets partout (Stratégie A)** : standard de production, fichiers de stack
  publiables sans risque.
- **IaC dès maintenant** : stacks dans le dépôt, déploiement depuis un clone.
  CD automatique reporté en Phase 4 (on automatise ce qui est validé).
- **Deploy key en lecture seule** : moindre privilège pour l'accès du manager
  au dépôt.
- **Segmentation réseau** : PostgreSQL et Redis jamais exposés, MinIO sur les
  deux réseaux.
- **Expansion runtime des secrets** (`$$(cat ...)`) pour Redis, là où PostgreSQL
  et MinIO supportent nativement les variables `_FILE`.

## Récap des commits

- `feat: add infra stacks directory with proxy and postgres`
- `feat: add redis stack with secret-based auth and AOF persistence`
- `feat: add minio stack with secret-based credentials`
- `feat: add week 5 journal — stateful services and IaC` (ce commit)

## Prochaine étape (Semaine 6)

- Authentification du cluster auprès de GHCR pour pull les images privées
- Script entrypoint pour que l'API Laravel lise les secrets Docker en variables
  d'environnement au démarrage
- Déploiement de l'API Laravel depuis GHCR, connectée à PostgreSQL, Redis, MinIO
- Exécution des migrations et seeders en production
- Déploiement du Web Next.js depuis GHCR
- Configuration des proxy hosts définitifs dans NPM (api, web) avec certificats
  Let's Encrypt
- Validation de bout en bout du vrai Kennelo en ligne
