# Observabilité et exploitation du cluster

Ce document décrit les outils d'administration du cluster (Portainer), leur
sécurisation, les procédures d'exploitation associées, et le fonctionnement
des déploiements par environnement.

## Portainer

Portainer fournit une interface graphique pour piloter le cluster Swarm : voir
les trois nœuds et leur consommation, les services, les conteneurs, les logs,
et effectuer des opérations (redémarrer, scaler, etc.).

### Architecture

Déployé via `infra/stacks/portainer.yml` :

- `portainer_agent` : un agent en mode global (une instance par nœud), avec
  accès au socket Docker local. C'est ce qui permet à Portainer de voir
  l'intérieur des trois nœuds.
- `portainer_portainer` : l'interface web, contrainte sur le manager, qui
  communique avec les agents via le réseau overlay dédié `agent_network`.

### Accès — tunnel SSH uniquement

L'interface n'est **pas** exposée publiquement. Le port 9000 est publié en
`mode: host` sur le manager, mais une règle de pare-feu (voir plus bas) bloque
tout accès externe. L'accès se fait par tunnel SSH :

```
ssh -L 9000:localhost:9000 kennelo@kennelo-manager
```

Puis dans le navigateur : http://localhost:9000

Un alias est défini côté poste de développement :

```
alias portainerkennelo='ssh -L 9000:localhost:9000 kennelo@kennelo-manager'
```

Ce choix (tunnel plutôt qu'exposition publique) est cohérent avec la décision
prise pour l'administration du reverse proxy : un composant capable de piloter
tout le cluster ne doit pas présenter de surface d'attaque publique.

## Sécurisation des ports d'administration

### Le problème

Docker contourne UFW : il écrit ses règles de publication de ports directement
dans iptables, en amont des règles UFW. Conséquence, un port publié par Docker
(comme le 9000 de Portainer) reste accessible depuis Internet même si UFW ne
l'autorise pas. C'est ce qui a été constaté : Portainer était joignable
publiquement malgré un UFW restrictif.

### La solution

La chaîne iptables `DOCKER-USER` est le point d'insertion prévu pour les règles
utilisateur, évaluée avant les règles de Docker. Le script
`infra/scripts/secure-admin-ports.sh` y insère, pour chaque port
d'administration, une règle qui accepte la loopback (pour le tunnel SSH) et
rejette tout le reste. Le script est idempotent (rejouable sans accumuler de
doublons). Les ports protégés sont 9000 (Portainer), 9090 (Prometheus) et
3000 (Grafana).

### Persistance au boot

Les règles iptables ne survivent pas à un redémarrage. Une unité systemd
(`infra/systemd/secure-admin-ports.service`) exécute le script au démarrage,
après Docker, de sorte que la protection se réapplique automatiquement à chaque
boot. Ceci a été validé par un redémarrage complet du manager : les règles
étaient bien présentes après le reboot.

### Installation (sur le manager)

```
sudo cp /home/kennelo/kennelo/infra/systemd/secure-admin-ports.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable secure-admin-ports.service
sudo systemctl start secure-admin-ports.service
```

Vérifier l'état :

```
sudo systemctl status secure-admin-ports.service
sudo iptables -L DOCKER-USER -n --line-numbers
```

### Vérifier que l'accès public est bien fermé

Depuis une machine externe (les trois doivent échouer / renvoyer 000) :

```
curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://46.101.124.234:9000
curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://104.248.252.27:9000
curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://165.227.136.219:9000
```

## Procédures d'exploitation

### Vérifier l'état de Portainer

```
docker service ls | grep portainer
docker service ps portainer_portainer
docker service ps portainer_agent
docker service logs portainer_portainer --tail 50
```

### Après un redémarrage du manager

Après un reboot du nœud manager, les agents Portainer des workers peuvent
rester désynchronisés du cluster interne (serf) : l'ancien agent manager est
marqué comme disparu et le nouvel agent n'est pas toujours réintégré
automatiquement. Symptôme : l'interface affiche par intermittence « Unable to
connect to the Docker environment ».

Correction : forcer le redémarrage des agents pour qu'ils reforment leur
cluster avec le manager courant.

```
docker service update --force portainer_agent
docker service update --force portainer_portainer
```

Attendre une vingtaine de secondes, puis recharger l'interface.

### Si l'écran d'initialisation Portainer expire

Si personne ne crée le compte administrateur dans les premières minutes
suivant le démarrage, Portainer se verrouille par sécurité (« timed out for
security purposes »). Il faut alors redémarrer le service pour réarmer la
fenêtre d'initialisation :

```
docker service update --force portainer_portainer
```

## Métriques — Prometheus et Grafana

Le cluster est instrumenté pour collecter et visualiser les métriques système
(CPU, RAM, disque, réseau) des trois nœuds et de chaque conteneur. C'est le
volet « état de santé » de l'observabilité.

### Architecture

Déployé via `infra/stacks/monitoring.yml` :

- `monitoring_node-exporter` : collecteur des métriques système de chaque nœud
  (CPU, RAM, disque, réseau de la machine). Mode global, une instance par nœud.
- `monitoring_cadvisor` : collecteur des métriques par conteneur (consommation
  CPU/RAM de chaque service). Mode global, une instance par nœud.
- `monitoring_prometheus` : collecte (scrape) les métriques des deux
  collecteurs toutes les 15 secondes et les stocke en série temporelle.
  Contraint sur le manager, données persistées dans le volume `prometheus_data`,
  rétention de 15 jours.
- `monitoring_grafana` : interface de visualisation, branchée sur Prometheus
  comme source de données. Contrainte sur le manager, configuration et
  dashboards persistés dans le volume `grafana_data`.

Tous ces services communiquent sur le réseau overlay dédié `monitoring`.

### Découverte des cibles

La configuration Prometheus (`infra/config/prometheus.yml`) utilise la
découverte de services par DNS propre à Swarm : les entrées `tasks.node-exporter`
et `tasks.cadvisor` résolvent vers les instances de chaque collecteur sur les
trois nœuds. Prometheus découvre donc automatiquement toutes les cibles, sans
adresses en dur ; un nœud ajouté au cluster est scrapé automatiquement.

### Accès — tunnel SSH uniquement

Ni Prometheus ni Grafana ne sont exposés publiquement. Les ports 9090 et 3000
sont publiés en `mode: host` sur le manager mais bloqués au public par le
pare-feu (voir « Sécurisation des ports d'administration »). L'accès se fait par
tunnel SSH :

```
ssh -L 9090:localhost:9090 kennelo@kennelo-manager   # Prometheus
ssh -L 3000:localhost:3000 kennelo@kennelo-manager   # Grafana
```

Alias définis côté poste de développement :

```
alias promkennelo='ssh -L 9090:localhost:9090 kennelo@kennelo-manager'
alias grafanakennelo='ssh -L 3000:localhost:3000 kennelo@kennelo-manager'
```

Puis dans le navigateur : http://localhost:9090 (Prometheus) ou
http://localhost:3000 (Grafana).

### Identifiants Grafana

Le compte administrateur Grafana est créé à partir d'un secret Docker
(`kennelo_grafana_admin_password`), pas du mot de passe par défaut. L'inscription
publique est désactivée (`GF_USERS_ALLOW_SIGN_UP=false`). Le mot de passe est
conservé dans le gestionnaire de mots de passe de l'équipe.

### Source de données et dashboards

La source de données Prometheus est configurée dans Grafana avec l'URL interne
`http://prometheus:9090` (nom du service sur le réseau monitoring). Les
dashboards utilisés sont importés depuis la bibliothèque communautaire :

- Node Exporter Full (métriques par nœud)
- cAdvisor / Docker (métriques par conteneur)

Note : la configuration Grafana (source de données et dashboards) est
actuellement faite via l'interface et persistée dans le volume `grafana_data`.
Un provisioning entièrement déclaratif (source et dashboards versionnés dans le
dépôt) pourra être ajouté ultérieurement pour une reproductibilité complète.

### Vérifier l'état de la collecte

```
docker service ls | grep monitoring
docker service ps monitoring_prometheus
```

Via le tunnel Prometheus, la page Status > Targets (http://localhost:9090/targets)
doit montrer toutes les cibles « UP » : prometheus (1), node-exporter (3),
cadvisor (3).

### Vérifier que l'accès public est fermé

Depuis une machine externe (doit renvoyer 000) :

```
curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://46.101.124.234:9090
curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://46.101.124.234:3000
```

## Déploiement et environnements

Kennelo vit sur deux environnements : la production (cluster Swarm 3 nœuds)
et la préproduction (VPS dédié mono-nœud, isolation complète). Le principe
directeur : la branche main alimente la préproduction, un tag git `vX.Y.Z`
déclenche une release de production. Chaque déploiement de production est
ainsi une décision explicite, jamais un effet de bord d'un merge.

La mécanique d'exécution (scripts, Makefile, variables d'environnement) est
documentée dans `infra/README.md` ; cette section couvre le cycle de vie des
images et la discipline de release.

### Architecture des tags d'images

La CI publie les images api, web et back-office sur GHCR avec des tags dont
le nom dit exactement ce qu'ils contiennent :

| Tag              | Produit sur       | Nature                                        |
| ---------------- | ----------------- | --------------------------------------------- |
| `preprod-latest` | push sur main     | Pointeur mobile, dernier merge, URLs préprod  |
| `vX.Y.Z`         | push d'un tag git | Immuable, une release précise, URLs prod      |
| `prod`           | push d'un tag git | Pointeur mobile vers la dernière release      |
| `sha-xxx`        | chaque build      | Immuable, un commit précis, pour rollback fin |

Les anciens tags `main` et `latest` ne sont plus produits : un tag ambigu sur
ce qu'il contient finit toujours par provoquer une erreur opérationnelle.

### Cycle de release standard

1. Une PR est mergée sur main. La CI builde et publie `:preprod-latest`, qui
   se déploie sur la préproduction (automatiquement, une fois le CD en
   place).
2. La version est validée sur la préproduction : parcours fonctionnels,
   vérification des points sensibles de la PR.
3. Un tag git `vX.Y.Z` est posé sur le commit validé et poussé. La CI builde
   et publie `:vX.Y.Z` et `:prod`.
4. La production est déployée sur ce tag (`make deploy-prod TAG=vX.Y.Z`, ou
   le CD une fois en place).

Un rollback est le redéploiement d'un tag antérieur — procédure détaillée
dans `infra/README.md`.

### Build par environnement pour l'image web

L'image web est construite une fois par environnement, car les variables
`NEXT_PUBLIC_*` (URL de l'API, hôte WebSocket) sont inlinées dans le bundle
client au moment du build : une image buildée avec les URLs de production ne
peut pas servir la préproduction. Les valeurs sont dérivées des fichiers
`infra/env/*.env`, la même source de vérité que les scripts de déploiement.

Cette stratégie est cohérente avec les builds mobiles Capacitor, distribués
par nature en bundles statiques construits par environnement : un seul
système de configuration pour le web et le mobile.

### Réflexion ouverte : configuration runtime

L'alternative — une image web unique lisant sa configuration au démarrage du
conteneur — supprimerait le build par environnement, au prix d'un refactoring
du front (les `NEXT_PUBLIC_*` ne peuvent plus être utilisés tels quels côté
client). Elle ne se justifierait que si les besoins changent : déploiement
multi-région, environnements éphémères par PR. Notée comme évolution
possible, pas au barème à court terme.

### Discipline de validation

Ne jamais poser un tag de release sans avoir vu tourner le
`:preprod-latest` correspondant sur la préproduction. C'est la contrepartie
du build par environnement : l'image de production n'étant pas l'artefact
exact testé en préproduction (les URLs diffèrent), la validation porte sur le
commit, et elle doit avoir eu lieu.

Le bug de configuration Google OAuth révélé à la bascule v0.1.0 (issue #93)
illustre exactement ce que cette discipline intercepte : un mois de commits
mergés sans jamais tourner devant un utilisateur, et le premier déploiement
qui les embarque découvre le problème en production. Avec une préproduction
alimentée à chaque merge, ce bug aurait été visible des semaines avant
d'atteindre un tag.

### Authentification Google (OAuth)

Le login Google repose sur un client OAuth de la Google Cloud Console,
branché de bout en bout depuis la correction de l'issue #93. Un seul client
est partagé entre production et préproduction : choix pragmatique tant
qu'aucun des deux environnements ne sert d'utilisateurs externes, les
origines autorisées du client listant simplement les deux domaines. Splitter
en deux clients distincts reste possible si un besoin d'isolation apparaît.

Le câblage suit la séparation public / privé habituelle :

- Le **client_id** est public par nature. Il vit dans les fichiers
  `infra/env/*.env` : inliné au build web via `NEXT_PUBLIC_GOOGLE_CLIENT_ID`
  (comme les autres `NEXT_PUBLIC_*`, voir « Build par environnement »), et
  injecté dans l'environnement du service api (`GOOGLE_CLIENT_ID`,
  `GOOGLE_REDIRECT_URI`) pour Socialite côté Laravel.
- Le **client_secret** est un Docker Secret
  (`kennelo_google_client_secret`), jamais présent dans le dépôt. Sa valeur
  venant de la Google Cloud Console, il est créé manuellement sur le manager
  de chaque environnement — même procédure que `kennelo_app_key`, rappelée
  par `bootstrap.sh` :

```
printf '%s' 'GOCSPX-...' | docker secret create kennelo_google_client_secret -
```

### Back-office d'administration

Le back-office (`apps/back-office`, image `kennelo-back-office`) suit le même
cycle de release que le web : image par environnement (le
`NEXT_PUBLIC_API_URL` est inliné au build), déployé par `deploy-app.sh` sur
`admin.preprod.kennelo.fr` (préprod) et `admin.kennelo.fr` (prod), un replica
par environnement.

Contrairement aux interfaces d'administration du cluster (Portainer,
Grafana), le back-office est exposé publiquement : c'est une application
métier avec sa propre authentification, pas un outil de pilotage de
l'infrastructure. Sa protection est multi-couche :

1. Un proxy Next.js server-side (`apps/back-office/proxy.ts`) garde
   `/dashboard/*` : token absent, expiré ou sans le rôle `admin` dans ses
   claims → redirection vers `/login`. Le proxy décode le JWT sans en
   vérifier la signature (le secret reste côté API) : il ferme la surface
   d'exposition de l'interface, il ne fait pas autorité.
2. Le hook client (`use-auth`) purge la session et refuse le login des
   comptes non-admin.
3. L'API Laravel reste la couche d'autorité : toutes les routes `/api/admin/*`
   sont derrière `auth.jwt` + `role:admin`, un token forgé ou dégradé y prend
   des 403 quelle que soit l'UI chargée.

Cas particulier des tokens d'impersonation : ils portent les rôles de
l'utilisateur cible, pas ceux de l'admin. Impersoner un utilisateur standard
produit donc un token que le back-office rejette — c'est voulu, ces tokens
servent à naviguer l'application web comme l'utilisateur, l'admin conserve
son propre token pour le back-office.

## CI/CD — Déploiement automatisé

L'infrastructure Kennelo utilise deux workflows GitHub Actions pour le
déploiement continu.

### Préproduction

Le workflow `deploy-preprod.yml` se déclenche automatiquement après tout push
sur `main` qui produit un build Docker Images CI réussi. Il exécute :

1. SSH sur le VPS préproduction (Hetzner CX23, IP 167.233.60.73)
2. `git pull` de main
3. `deploy-app.sh` avec `ENV=preprod` — rolling update des services api, web,
   reverb sur `:preprod-latest`
4. `migrate.sh` — application des nouvelles migrations et seeders de référence
5. Health check sur `https://preprod.kennelo.fr/api/health`

Aucune intervention manuelle n'est requise pour la préprod : les données de
test peuvent être régénérées à volonté.

### Production

Le workflow `deploy-prod.yml` se déclenche automatiquement après tout push
d'un tag `v*.*.*` qui produit un build Docker Images CI réussi. Il exécute :

1. SSH sur le manager Swarm (DigitalOcean, IP 46.101.124.234)
2. `git checkout <tag>`
3. `deploy-app.sh` avec `ENV=prod` et `IMAGE_TAG=<tag>` — rolling update sur
   les 2 workers
4. Check des migrations en attente sur les containers api : si des migrations
   Pending existent, le workflow échoue en signalant qu'il faut lancer
   `migrate.sh` manuellement sur un worker
5. Health check sur `https://kennelo.fr/api/health`

Les migrations en prod restent volontairement manuelles pour prévenir toute
perte de données silencieuse. Le workflow utilise le rebond SSH
manager→worker (via la clé `~/.ssh/id_ed25519` de kennelo sur le manager,
autorisée sur les workers) pour vérifier l'état des migrations dans le
container api.

### Clés SSH dédiées

Les workflows utilisent deux paires de clés SSH ed25519 dédiées, distinctes
des clés d'accès humaines. Les clés privées vivent dans les GitHub Secrets
`SSH_PRIVATE_KEY_PREPROD` et `SSH_PRIVATE_KEY_PROD`. Les clés publiques sont
ajoutées aux `authorized_keys` de l'user `kennelo` sur chaque cible. Cette
séparation permet de révoquer indépendamment les accès CI/CD sans impacter
les accès humains.

### Rollback

En cas de problème après un déploiement prod, la remise en état passe par le
retag :

- SSH sur le manager, `git checkout v<version-precedente>`
- `ENV=prod IMAGE_TAG=v<version-precedente> ./infra/scripts/deploy-app.sh`

Les images `:vX.Y.Z` sont immuables (produites une seule fois par la CI), ce
qui garantit qu'un rollback vers une ancienne version tape sur le même
binaire que le déploiement original.

## Sauvegardes — politique 3-2-1

La politique de recouvrement couvre le contenu de la base de données et les
fichiers utilisateurs (bucket MinIO `kennelo-media`). Sont exclus, car
reconstructibles : Redis (cache et queues), les images Docker (rebuildées
par la CI) et la configuration (versionnée dans git).

### Mapping 3-2-1

| Exigence                 | Réalisation                                             |
| ------------------------ | ------------------------------------------------------- |
| 3 sauvegardes identiques | volume local du manager + DO Spaces + Cloudflare R2     |
| 2 médiums différents     | disque (volume local) et stockage objet S3 (Spaces, R2) |
| 1 sauvegarde externe     | Cloudflare R2, fournisseur distinct de DigitalOcean     |

### Fonctionnement

La stack `backup` (cycle de vie admin, prod uniquement, épinglée sur le
manager) exécute chaque nuit à 04h00 UTC le script `backup.sh` de l'image
`kennelo-backup` (`infra/backup/`, versionnée et buildée par la CI) :

1. `pg_dump` au format custom, chiffré en AES256 (gpg symétrique, passphrase
   dans le secret `kennelo_backup_gpg_passphrase` — également conservée dans
   le gestionnaire de mots de passe de l'équipe), écrit atomiquement sur le
   volume local `kennelo-backups`
2. copie incrémentale des médias MinIO vers le volume local, sans
   propagation des suppressions (un delete accidentel en production ne
   détruit pas l'historique sauvegardé)
3. envoi de l'ensemble vers les remotes rclone `spaces` et `r2` (secret
   `kennelo_backup_rclone_conf`)
4. rétention : 7 dumps quotidiens, 4 hebdomadaires (copie du dimanche),
   purge appliquée sur les trois cibles
5. ping healthchecks.io en fin de run (`/fail` en cas d'erreur) : un échec
   de sauvegarde déclenche une alerte email, l'échec silencieux est
   impossible

Vérifier l'état : `docker service logs backup_backup --tail 50` sur le
manager, et le dashboard healthchecks.io.

### Runbook — restauration de la production

Scénario : perte ou corruption de la base et/ou des médias. À dérouler
depuis le manager prod, calmement, dans l'ordre.

1. Évaluer : `docker service ls`, logs de l'api, état de postgres. Décider
   du périmètre (base seule, médias seuls, les deux).
2. Couper l'écriture applicative :
   `docker service scale api_api=0 reverb_reverb=0`
3. Ouvrir un shell dans le container de backup :
   `docker exec -it $(docker ps -q -f name=backup_backup | head -1) bash`
4. Lister les dumps disponibles (au choix : `local`, `spaces`, `r2`) :
   `ls /backups/db/daily` ou
   `rclone --config /run/secrets/kennelo_backup_rclone_conf lsf r2:kennelo-backups/db/daily`
5. Restaurer — le script demande une confirmation tapée :
   `restore.sh --source r2 --latest` (ajouter `--db-only` ou `--media-only`
   pour restreindre, `--file db/daily/<nom>.dump.gpg` pour une date précise)
6. Relancer : `docker service scale api_api=2 reverb_reverb=2`
7. Vérifier : `curl https://kennelo.fr/api/health`,
   `curl https://api.kennelo.fr/up`, un parcours de connexion, et
   `php artisan migrate:status` dans un container api (rebond worker)
8. Consigner l'incident : cause, dump utilisé, durée d'indisponibilité.

Le cycle complet (sauvegarde, sinistre simulé, restauration depuis la copie
externe) a été validé de bout en bout lors de la mise en place, et chaque
restauration réelle ou de test doit être notée ici avec sa date.

Historique des restaurations :

- 2026-07-15 — restauration de test (mise en service) : dump du premier run
  de production restauré depuis R2 dans une base jetable, 68 tables et
  comptages vérifiés. Chaîne complète validée en conditions réelles.
