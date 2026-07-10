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

La CI publie les images api et web sur GHCR avec des tags dont le nom dit
exactement ce qu'ils contiennent :

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
