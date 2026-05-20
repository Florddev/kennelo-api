# Semaine 6 — Déploiement applicatif : Kennelo en production

## Contexte

Dernière semaine de la Phase 2. Le cluster Docker Swarm est opérationnel
(Semaine 4) et les trois services stateful — PostgreSQL, Redis, MinIO —
tournent avec leurs secrets (Semaine 5). Il reste à déployer le cœur de
l'application : l'API Laravel et le front Next.js, depuis les images publiées
sur GHCR, puis à valider que l'ensemble fonctionne de bout en bout en HTTPS.

C'est la semaine où Kennelo passe de « infrastructure prête » à « application
réellement en ligne ».

## Objectif

- Authentifier le cluster auprès de GHCR pour récupérer les images privées
- Rendre l'entrypoint de l'API capable de lire les secrets Docker
- Déployer l'API Laravel (deux réplicas) connectée aux services stateful
- Exécuter les migrations et les seeders de référence en production
- Déployer le front Next.js (deux réplicas)
- Exposer l'API et le front en HTTPS via Nginx Proxy Manager
- Valider l'authentification de bout en bout
- Capturer la procédure de déploiement dans des scripts réutilisables

## Durée effective

~3 heures, dans la continuité des Semaines 4 et 5.

## Réalisations

### 1. Authentification du cluster auprès de GHCR

Les images applicatives sont privées sur GHCR. Le nœud manager s'y est
authentifié avec un PAT GitHub dédié (scope `read:packages` uniquement), via
`docker login ghcr.io`. Comme les nœuds DigitalOcean sont en x86_64, le manager
récupère directement les images amd64 produites par la CI, sans avoir à forcer
de plateforme.

Pour les déploiements, le drapeau `--with-registry-auth` est utilisé : il
distribue les identifiants du registre du manager vers les workers, afin que
ceux-ci puissent eux aussi récupérer les images privées (l'application tourne
sur les workers).

### 2. Entrypoint sensible aux secrets

L'API lit ses identifiants via des variables d'environnement, mais les mots de
passe sont stockés dans des secrets Docker (fichiers sous `/run/secrets`).
L'entrypoint de l'API a été enrichi d'une fonction `load_secret` qui lit chaque
fichier secret et l'exporte en variable d'environnement.

Point important : ce chargement doit se faire **avant** `php artisan
config:cache`. Sinon, Laravel met en cache une configuration sans les
identifiants, et l'application démarre sans pouvoir se connecter à ses
services. L'ordre a donc été corrigé pour charger les secrets en premier.

Les secrets chargés : `APP_KEY`, `JWT_SECRET`, `DB_PASSWORD`,
`REDIS_PASSWORD`, et les identifiants MinIO mappés sur les variables AWS.

### 3. Déploiement de l'API

L'API est déployée en deux réplicas, contraints à tourner sur les workers (le
manager est réservé aux services de données). La configuration non sensible
(hôtes des services, locales, CORS, endpoint S3) est définie en clair dans le
fichier de stack ; seules les valeurs sensibles passent par des secrets.

Quelques points de configuration notables pour la production : `APP_ENV` à
production, `APP_DEBUG` à false, l'endpoint MinIO interne
(`http://minio:9000`) distinct de l'URL publique des médias
(`https://cdn.kennelo.fr/...`), et la diffusion temps réel (Reverb)
volontairement désactivée pour ce premier déploiement.

Les deux réplicas ont démarré correctement et répondu 200 sur la route de
santé `/up`.

### 4. Migrations et seeders en production

Les commandes Artisan one-shot (migrations, seeders) ont été exécutées dans un
conteneur de l'API. Comme les réplicas tournent sur les workers, ces commandes
sont lancées depuis un worker, pas depuis le manager.

Les migrations ont créé les 51 tables de Kennelo sans erreur, ce qui a validé
du même coup la connexion à PostgreSQL via le réseau interne et la lecture
correcte du secret de mot de passe.

Pour les seeders, une décision importante a été prise (voir plus bas) : seuls
les seeders de référence ont été appliqués (rôles, types d'animaux, attributs,
critères d'avis). Résultat : 3 rôles, 3 utilisateurs de service, 12 types
d'animaux.

### 5. Déploiement du front Next.js

Le front est déployé en deux réplicas sur les workers, sur le réseau public
uniquement (il ne parle pas directement aux bases : il passe par l'API via
`api.kennelo.fr`). Aucun secret n'est nécessaire côté front.

Particularité Next.js : les variables `NEXT_PUBLIC_*` sont figées dans le
bundle JavaScript au moment du build, pas au runtime. L'URL de l'API de
production devait donc être présente lors de la construction de l'image. Le
Dockerfile a été adapté pour accepter ces variables en arguments de build (avec
des valeurs localhost par défaut pour le développement), et la CI les fournit
avec les valeurs de production.

### 6. Exposition HTTPS via Nginx Proxy Manager

Deux hôtes proxy ont été créés dans NPM, chacun avec un certificat Let's
Encrypt généré automatiquement et la redirection HTTPS forcée :

- `api.kennelo.fr` vers le service `api` (port 80)
- `kennelo.fr` et `www.kennelo.fr` vers le service `web` (port 3000)

Le nom de service Swarm (`api`, `web`) est résolu par le DNS interne du réseau
overlay, et le routage répartit automatiquement la charge entre les deux
réplicas de chaque service.

### 7. Validation de bout en bout

`api.kennelo.fr/up` répond 200 en HTTPS. `kennelo.fr` sert la page d'accueil,
avec le middleware d'internationalisation actif (réécriture vers la locale,
en-têtes hreflang, cookie de locale). Après résolution des incidents ci-dessous,
l'authentification fonctionne : un utilisateur peut se connecter depuis
`kennelo.fr`, le front appelle l'API, l'API émet un token JWT, et la session
s'ouvre. Kennelo est en ligne et fonctionnel.

### 8. Scripts de déploiement

La procédure manuelle a été capturée dans trois scripts sous `infra/scripts/` :
`bootstrap.sh` (réseaux et secrets, à lancer une fois), `deploy.sh` (pull des
images et déploiement de toutes les stacks) et `migrate.sh` (migrations et
seeders de référence). Ils transforment une suite de commandes en procédure
reproductible. L'automatisation complète (déploiement continu) est prévue pour
la Phase 4.

## Incidents et décisions

### Faker absent en production (garde-fou bienvenu)

Le seed complet a échoué sur le seeder d'établissements avec « Call to undefined
function fake() ». Cause : l'image de production est construite avec
`composer install --no-dev`, qui exclut le paquet Faker (dépendance de
développement). C'est le comportement correct : une image de production ne doit
pas embarquer d'outil de génération de fausses données. Conséquence pratique :
seuls les seeders de référence (qui n'utilisent pas Faker) peuvent tourner en
production, ce qui correspond exactement à ce qu'on veut. L'erreur a agi comme
un garde-fou empêchant l'injection de données de démonstration en production.

### Sécurisation des comptes de service

Le seeder d'utilisateurs créait trois comptes (admin, manager, user) avec des
mots de passe triviaux identiques à leur rôle. Ces comptes ayant été créés
avant l'échec du seed, leurs mots de passe ont été immédiatement remplacés par
des valeurs fortes générées aléatoirement, avant toute exposition publique de
l'API. Recommandation transmise à l'équipe applicative : ne jamais committer de
mots de passe en dur, même pour des comptes de démonstration. À terme, la base
sera vidée et reseedée proprement, et le premier administrateur créé
manuellement.

### Healthcheck du front en échec (IPv4 contre IPv6, puis i18n)

Le front démarrait correctement mais le healthcheck le tuait en boucle. Deux
causes superposées. D'abord, le test utilisait `localhost`, que l'outil wget
résout en IPv6 (::1) en premier, alors que Next.js n'écoute qu'en IPv4 :
« connexion refusée ». Le passage à `127.0.0.1` a corrigé ce point. Ensuite,
même en IPv4, la racine `/` renvoie 404 : le middleware d'i18n ne sait pas
mapper une locale quand l'en-tête Host est une adresse IP plutôt qu'un domaine
connu. Le serveur est donc parfaitement sain — c'est le test de santé qui était
inadapté. Le healthcheck a été désactivé pour débloquer le déploiement ; une
route de santé indépendante du domaine sera ajoutée plus tard. La politique de
redémarrage de Swarm continue de relancer un conteneur en cas de vrai crash.

### JWT_SECRET manquant (dernier incident)

Le login renvoyait 500. L'API utilise l'authentification JWT (HS256), qui a
besoin d'un secret pour signer les tokens. Cette variable n'avait pas été
incluse dans les secrets initiaux — on avait géré la clé applicative, la base,
le cache et le stockage, mais pas JWT. Diagnostic confirmé en constatant que la
configuration JWT était vide. Un secret Docker dédié a été créé, chargé par
l'entrypoint et déclaré dans la stack. Après redéploiement, l'API est passée de
500 à 422 (validation, preuve que JWT fonctionnait), puis à un login réussi.
Leçon : recenser **toutes** les variables sensibles d'une application avant le
déploiement, pas seulement les plus évidentes.

## Validation finale

- API en HTTPS : `https://api.kennelo.fr/up` répond 200
- Front en HTTPS : `https://kennelo.fr` sert la page d'accueil, i18n actif
- Authentification de bout en bout fonctionnelle (front → API → JWT → session)
- Six secrets Docker en place, aucun mot de passe en clair
- Comptes de service sécurisés avec des mots de passe forts
- Migrations (51 tables) et seeders de référence appliqués
- Procédure de déploiement capturée dans des scripts

## Architecture en production

```
Navigateur (https://kennelo.fr)
   -> Cloudflare (DNS)
   -> Manager :443 (Nginx Proxy Manager + Let's Encrypt)
        -> web   (Next.js, 2 réplicas, workers)
        -> api   (Laravel, 2 réplicas, workers) -> JWT
              -> postgres / redis / minio (manager, réseau interne)
```

## Récap des commits

- `feat: load Docker secrets in API entrypoint before config cache`
- `feat: add API stack for Swarm deployment`
- `feat: bake production NEXT_PUBLIC vars into web image at build`
- `feat: add web stack for Swarm deployment`
- `feat: web healthcheck uses IPv4 (127.0.0.1) instead of localhost`
- `feat: disable web healthcheck pending domain-aware health route`
- `feat: load JWT_SECRET from Docker secret in API`
- `feat: add infra deployment scripts (bootstrap, deploy, migrate)`
- `feat: add week 6 journal — application deployment` (ce commit)

## Prochaine étape (Phase 3, Semaine 7)

- Déploiement de Reverb pour le temps réel (WebSockets sur `ws.kennelo.fr`)
- Route de santé indépendante du domaine pour réactiver le healthcheck du front
- Workers de queue et planificateur (scheduler) Laravel
- Configuration multi-domaine complète (kennelo.com et autres)
- Début de l'observabilité (métriques, logs centralisés)
