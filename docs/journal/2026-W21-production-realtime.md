# Semaine 7 — Consolidation de la production et temps réel

## Contexte

Kennelo est en ligne depuis la fin de la Phase 2 : front Next.js, API Laravel,
services stateful, le tout en HTTPS sur le cluster Docker Swarm. Cette semaine
ouvre la Phase 3. L'objectif est double : consolider ce qui avait été laissé en
état imparfait lors de la mise en ligne, et ajouter la brique temps réel
(WebSockets) avec Laravel Reverb, déployée proprement et scalable.

C'est aussi la semaine où l'infrastructure atteint un état suffisamment propre
et complet pour être fusionnée sur la branche principale.

## Objectif

- Réparer le healthcheck du service web, désactivé en urgence à la mise en ligne
- Sécuriser l'accès à l'interface d'administration du reverse proxy
- Corriger les incohérences de CI révélées par la migration applicative
- Déployer Laravel Reverb comme service dédié, scalable horizontalement
- Valider le temps réel de bout en bout
- Préparer une version propre de l'infrastructure pour la fusion sur main

## Durée effective

Une session de travail prolongée.

## Réalisations

### 1. Réparation du healthcheck du service web

À la mise en ligne, le healthcheck du conteneur web avait été désactivé en
urgence : il testait la racine `/`, mais le middleware d'internationalisation
renvoie un 404 sur `/` quand l'en-tête Host est une adresse IP plutôt qu'un
domaine connu (pas de mapping de locale possible). Le serveur était sain, mais
le test de santé inadapté tuait le conteneur en boucle.

La correction consiste à ajouter une route de santé indépendante du domaine :
`/api/health`, un Route Handler Next.js qui renvoie 200 quel que soit le Host.
Cette route vit sous `/api`, que le matcher du middleware exclut déjà, donc
elle n'est jamais interceptée par la logique i18n. Le healthcheck (dans le
Dockerfile et dans la stack) cible désormais cette route, en IPv4 (`127.0.0.1`)
pour éviter que `localhost` ne soit résolu en IPv6 alors que Next.js n'écoute
qu'en IPv4. Les conteneurs web sont de nouveau rapportés « healthy », et Swarm
peut à nouveau détecter un vrai plantage.

### 2. Sécurisation de l'accès à l'administration du reverse proxy

L'interface d'administration de Nginx Proxy Manager était jusque-là accessible
uniquement par tunnel SSH. La tentative de l'exposer publiquement sur un
sous-domaine, protégée par une liste d'accès (authentification HTTP basic), a
révélé deux choses. D'abord un piège de configuration : avec l'option « Satisfy
Any » décochée, NPM exige de satisfaire toutes les conditions, y compris une
règle d'adresse IP ; sans règle IP, un « deny all » implicite bloque tout (403).
Il faut cocher « Satisfy Any » pour que l'authentification seule suffise.

Ensuite, un constat plus structurel : l'interface NPM servie à travers NPM
lui-même (le proxy se routant vers sa propre UI) ne fonctionne pas correctement
avec une couche d'authentification basic — l'interface boucle indéfiniment sur
la demande d'authentification, car ses appels API internes ne s'accommodent pas
de cette couche.

Décision retenue : ne pas exposer l'administration du reverse proxy
publiquement. Elle reste accessible uniquement par tunnel SSH, ce qui est à la
fois plus sûr (aucune surface d'attaque exposée sur un composant critique qui
contrôle tout le routage et les certificats) et conforme aux bonnes pratiques
(beaucoup d'infrastructures gardent leur administration derrière un tunnel ou
un VPN). Un alias shell a été ajouté côté poste de développement pour rendre
l'ouverture du tunnel aussi simple qu'une commande.

### 3. Corrections de CI induites par la migration applicative

La migration de la Phase 1 (PostgreSQL et stockage S3) avait modifié les
valeurs par défaut applicatives, ce qui a révélé deux incohérences dans la
chaîne d'intégration continue de tests, sans rapport direct avec
l'infrastructure mais déclenchées par elle :

- Le workflow de tests exécutait une étape `migrate` qui lisait le `.env`
  désormais configuré en PostgreSQL et tentait de joindre une base absente du
  runner. Cette étape était redondante : les tests s'exécutent sur SQLite en
  mémoire et migrent eux-mêmes. Étape supprimée.
- Les tests d'upload échouaient car le disque média par défaut était passé sur
  S3, indisponible en test. Le disque média est désormais épinglé sur un disque
  local dans l'environnement de test, ce qui réaligne la configuration avec les
  mocks déjà présents dans les tests, sans effet sur le développement ni la
  production.

Ces corrections, bien que touchant des fichiers à la frontière de
l'applicatif, relevaient de la responsabilité de la migration : un changement
d'infrastructure qui casse la CI doit réparer ce qu'il a cassé.

### 4. Déploiement de Reverb avec scaling horizontal

Laravel Reverb (serveur WebSocket pour le temps réel) a été déployé comme
service Swarm dédié, à partir de la même image que l'API mais avec une commande
différente (`reverb:start`). Deux réplicas tournent sur les workers, avec le
scaling horizontal activé : chaque instance publie et souscrit sur un canal
Redis partagé, de sorte qu'un événement émis sur n'importe quel réplica atteint
les clients connectés à n'importe quel autre. C'est la coordination
indispensable dès qu'un service WebSocket est répliqué.

Deux obstacles ont dû être franchis, tous deux typiques du fait de faire
tourner un démon long-running sur une image conçue pour du HTTP :

- **Extension `pcntl` manquante** : Reverb s'abonne aux signaux système
  (SIGINT, SIGTERM) pour s'arrêter proprement, ce qui requiert l'extension PHP
  `pcntl`. Absente de l'image (php-fpm n'en a pas besoin), elle provoquait un
  fatal au démarrage (« Undefined constant SIGINT »). Ajoutée à la liste des
  extensions compilées.
- **Healthcheck inadapté** : le service Reverb héritait du healthcheck de
  l'image API, qui teste un endpoint HTTP sur le port 80 servi par nginx. Or
  Reverb n'a pas de nginx et écoute sur le port 8080. Le test échouait
  systématiquement et le conteneur était tué (exit 137). La stack override
  désormais le healthcheck pour cibler l'endpoint de santé propre de Reverb sur
  le port 8080.

L'API a par ailleurs été reconfigurée pour diffuser via Reverb
(`BROADCAST_CONNECTION` passé de `log` à `reverb`), avec un secret Docker dédié
pour le secret d'application Reverb (la clé d'application, elle, est publique
côté client et reste en clair). Reverb est exposé en HTTPS via un hôte proxy
`ws.kennelo.fr` avec le support WebSocket activé.

### 5. Validation du temps réel

L'endpoint de santé de Reverb répond en HTTPS via le proxy. Une connexion
WebSocket établie depuis le navigateur reçoit bien l'événement
`pusher:connection_established` avec un identifiant de socket, ce qui prouve
que la chaîne complète fonctionne : navigateur, TLS, proxy avec négociation
WebSocket, serveur Reverb, coordination Redis. Le front initialise sa connexion
après authentification (canaux privés), comportement attendu.

## Validation

- Healthcheck web réparé, conteneurs « healthy »
- Administration du reverse proxy accessible uniquement par tunnel SSH
- Chaîne de CI de tests entièrement au vert
- Reverb déployé en deux réplicas, healthy, avec scaling Redis
- Connexion WebSocket établie de bout en bout en HTTPS

## Décisions et points techniques

- **Route de santé sous `/api`** : tire parti de l'exclusion du middleware par
  le matcher, garantissant une réponse 200 indépendante du domaine.
- **Administration NPM par tunnel uniquement** : choix de sécurité (moindre
  surface) plutôt que d'exposer un composant critique, après avoir constaté que
  l'exposition derrière une authentification basic ne fonctionne pas pour cette
  interface.
- **Réparer ce que la migration casse** : les corrections de CI sont assumées
  comme partie intégrante de la migration, même sur des fichiers applicatifs.
- **Reverb en service dédié + scaling Redis** : séparation des responsabilités
  et scalabilité horizontale dès le départ, conformément à l'objectif de
  qualité de production.
- **`pcntl` pour les démons** : extension désormais présente, utile aussi pour
  les futurs workers de queue.

## Prochaine étape (suite de la Phase 3)

Avec cet état, l'infrastructure est cohérente et complète sur son périmètre, et
sera fusionnée sur la branche principale. La suite, sur une nouvelle branche :

- Observabilité : métriques, logs centralisés, supervision des services
- Workers de queue et planificateur Laravel
- Configuration multi-domaine complète (kennelo.com et autres)
- Optimisation éventuelle de la communication interne API vers Reverb
