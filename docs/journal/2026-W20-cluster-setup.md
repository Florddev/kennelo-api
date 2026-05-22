# Semaine 4 — Provisioning du cluster Docker Swarm

## Contexte

Première semaine de la Phase 2 (setup cluster). Après avoir préparé toute la
partie applicative en Phase 1 (PostgreSQL, Redis, MinIO, Dockerfiles de
production, CI/CD vers GHCR), il faut maintenant construire l'infrastructure
qui hébergera Kennelo en production : un cluster Docker Swarm de trois nœuds
sur DigitalOcean, sécurisé, avec un reverse proxy capable de gérer les
certificats TLS automatiquement.

L'objectif de la semaine est d'arriver à un cluster fonctionnel servant au
moins un service en HTTPS avec un certificat Let's Encrypt valide.

## Objectif

- Provisionner trois VPS sur DigitalOcean (1 manager 4 GB + 2 workers 2 GB)
- Durcir chaque serveur (utilisateur non-root, firewall, fail2ban, SSH par clé)
- Installer Docker et initialiser le cluster Swarm
- Migrer le DNS vers Cloudflare et configurer les enregistrements
- Déployer Nginx Proxy Manager et valider le premier HTTPS du cluster

## Durée effective

~5 heures sur une session, incidents de récupération inclus.

## Architecture retenue

Le choix d'architecture a été débattu. Trois options étaient sur la table :

1. Un manager léger dédié + workers pour la donnée et l'app
2. Trois managers en haute disponibilité (quorum Raft) + workers
3. Un manager qui héberge aussi la donnée + deux workers pour l'application

L'option 3 a été retenue. Le manager (4 GB) héberge l'orchestration et les
services stateful (PostgreSQL, Redis, MinIO, le proxy, le monitoring à venir).
Les deux workers (2 GB chacun) hébergeront les services applicatifs répliqués
(API, Web, workers de queue).

La haute disponibilité réelle (trois managers + réplication PostgreSQL +
Redis Sentinel + MinIO distribué) a été écartée volontairement : elle
transformerait un projet étudiant en infrastructure d'ingénieur SRE senior,
pour un bénéfice marginal sur un service de 100 utilisateurs. Le point unique
de défaillance que représente le manager est assumé et compensé par la
stratégie de sauvegarde 3-2-1 prévue en Phase 4. Coût mensuel : ~48 $, soit
environ quatre mois de fonctionnement sur le crédit GitHub Student Pack
restant.

## Réalisations

### 1. Provisionnement des trois VPS

Création d'un projet `kennelo-swarm` sur DigitalOcean. Les anciens droplets
d'un projet précédent ont d'abord été détruits pour ne pas gaspiller le
crédit. Trois droplets Ubuntu 24.04 LTS dans la région Frankfurt (FRA1), avec
la clé SSH publique injectée dès la création.

| Rôle     | Public          | Privé (VPC) | Taille        |
| -------- | --------------- | ----------- | ------------- |
| manager  | 46.101.124.234  | 10.114.0.2  | 4 GB / 2 vCPU |
| worker-1 | 104.248.252.27  | 10.114.0.3  | 2 GB / 1 vCPU |
| worker-2 | 165.227.136.219 | 10.114.0.4  | 2 GB / 1 vCPU |

Observation importante : DigitalOcean attache deux réseaux privés. `eth0`
porte une IP `10.19.0.X` partagée entre clients de la région FRA1, tandis que
`eth1` porte une IP `10.114.0.X` qui est le VPC dédié et isolé du projet.
Toute la communication interne du Swarm doit passer par le VPC dédié (eth1),
pour la sécurité comme pour la performance.

Des alias SSH ont été ajoutés au `~/.ssh/config` local
(`kennelo-manager`, `kennelo-worker-1`, `kennelo-worker-2`) pour éviter de
manipuler les IP à la main.

### 2. Durcissement de chaque nœud

Sur chaque serveur, un script de hardening a appliqué :

- Mise à jour complète du système (`apt upgrade`), avec passage du kernel de
  6.8.0-71 à 6.8.0-117 et reboot. Le hostname du worker-1, auto-suffixé en
  `swarm-worker-1-01` par DigitalOcean, a été corrigé.
- Création d'un utilisateur `kennelo` non-root avec `sudo` sans mot de passe
  (pratique pour l'automatisation Ansible à venir).
- Installation et configuration d'UFW : seuls les ports nécessaires sont
  ouverts (22 pour SSH, 80 et 443 pour le web, 2377/7946/4789 pour Swarm).
- Installation de fail2ban pour bannir automatiquement les tentatives de
  bruteforce SSH.
- Désactivation du login root et de l'authentification par mot de passe dans
  `sshd_config`. Seule l'authentification par clé est désormais possible.

La désactivation du root a été faite avec prudence : test de la connexion
`kennelo` validé avant de couper le root, et conservation d'une session active
pour rollback éventuel. Les logs SSH montraient déjà des bots tentant de se
connecter en root depuis plusieurs IP étrangères ; le durcissement a été
immédiatement utile.

### 3. Incident — auto-bannissement par fail2ban

La configuration fail2ban initiale (`maxretry=5`, `bantime=1h`, sans
whitelist) m'a banni de mes propres trois serveurs après des tentatives de
test `ssh root@...`. Le symptôme caractéristique était un `Connection
refused` (et non un `Permission denied`), indiquant un blocage réseau et non
un refus de clé.

Récupération via la console web de DigitalOcean : reset du mot de passe root,
login en root via la console (qui contourne SSH), puis `fail2ban-client set
sshd unbanip` et ajout d'une whitelist permanente dans
`/etc/fail2ban/jail.local` via la directive `ignoreip` incluant mon IP de
développement. La même opération a dû être répétée sur les trois nœuds.

Piège technique noté au passage : la console web de DigitalOcean ne préserve
pas les retours à la ligne lors d'un copier-coller d'un heredoc
(`cat <<EOF`). Il faut éditer les fichiers avec `nano` dans ce contexte plutôt
que coller un bloc multi-lignes.

Leçon retenue : toujours whitelister l'IP de l'opérateur avant de durcir
fail2ban. Cette whitelist sera gérée automatiquement par le playbook Ansible
en Phase 4 (lecture de l'IP admin depuis l'inventaire).

### 4. Installation de Docker — choix de version

Point de vigilance important. La dernière version stable de Docker au moment
de l'installation est la 29.x, mais plusieurs sources documentent des bugs en
mode Swarm sur cette branche : services qui ne rejoignent pas le réseau
overlay, problèmes de résolution DNS interne, et une montée de l'API minimale
à la version 1.44 qui casse des outils plus anciens.

Décision : pinner Docker à la dernière 28.x
(`5:28.5.1-1~ubuntu.24.04~noble`), version stable et éprouvée avec Swarm. Les
paquets Docker sont marqués `apt-mark hold` pour éviter qu'un futur
`apt upgrade` ne les remonte accidentellement vers la 29.x. Une réévaluation
de Docker 29 (probablement 29.5+ ou 30) est prévue lors d'une revue technique
ultérieure, une fois le support Swarm stabilisé.

Docker 28.5.1 et Compose 5.1.3 installés sur les trois nœuds. L'utilisateur
`kennelo` a été ajouté au groupe `docker` (une reconnexion SSH est nécessaire
pour que le changement de groupe prenne effet).

### 5. Initialisation du Swarm

Sur le manager :

```bash
docker swarm init --advertise-addr 10.114.0.2
```

L'IP utilisée est celle du VPC privé dédié (eth1), pas l'IP publique. Cela
garantit que toute la communication inter-nœuds passe par le réseau privé :
gratuit, à faible latence, et non exposé à Internet.

Les deux workers ont rejoint le cluster avec le token fourni. Vérification :

```
ID                            HOSTNAME         STATUS    MANAGER STATUS   ENGINE
bi8jhrejclwftk1rcr3jcgrse *   swarm-manager    Ready     Leader           28.5.1
84ol9i71zbiatvuhv6n7ym7l5     swarm-worker-1   Ready                      28.5.1
nyyh786ok15hv2yaiz6p17bpt     swarm-worker-2   Ready                      28.5.1
```

Trois nœuds Ready, version d'engine cohérente, manager en Leader. Le cluster
est opérationnel.

### 6. Migration DNS vers Cloudflare

Les domaines (`kennelo.fr`, `kennelo.com`) sont enregistrés chez Amen.fr.
Plutôt que d'utiliser l'interface DNS d'Amen (rugueuse, sans API, TTL élevés),
le DNS a été délégué à Cloudflare (plan Free) : interface moderne, TTL bas,
API DNS gratuite (utile pour l'automatisation Ansible et le challenge DNS-01
plus tard), protection DDoS, et surtout la gestion fine du proxy par
enregistrement.

Cloudflare permet aussi le partage d'accès entre plusieurs membres
gratuitement (rôles Administrator, DNS, Auditor…), ce qui résout le besoin
d'accès partagé au DNS entre membres de l'équipe.

Après bascule des nameservers (mario.ns.cloudflare.com /
rihana.ns.cloudflare.com), les deux zones sont passées en statut « Active ».

### 7. Configuration des enregistrements DNS

Onze enregistrements A ont été configurés pour `kennelo.fr`, tous pointant
vers l'IP publique du manager (46.101.124.234) : la racine, le wildcard `*`,
`www`, puis `api`, `ws`, `cdn`, `s3`, `npm`, `grafana`, `dozzle`, `uptime`,
`analytics`. Un seul reverse proxy (Nginx Proxy Manager) dispatche ensuite
selon l'en-tête `Host` de chaque requête.

Les enregistrements liés à la messagerie (MX, TXT SPF, CNAME mail/smtp/webmail
/autoconfig) ont été soigneusement préservés pour ne pas casser les emails du
domaine.

Décision technique notable : tous les enregistrements sont en mode « DNS only »
(proxy Cloudflare désactivé) et non « Proxied ». La raison est que Let's
Encrypt doit pouvoir atteindre directement le serveur pour valider le
challenge HTTP-01 ; si le proxy Cloudflare était actif, c'est Cloudflare qui
répondrait au challenge et l'émission du certificat échouerait. Le passage en
mode Proxied (pour bénéficier du CDN et du masquage d'IP) est prévu en Phase 3,
une fois les certificats en place et avec un renouvellement basé sur le
challenge DNS-01.

### 8. Déploiement de Nginx Proxy Manager

Création d'un réseau overlay `kennelo-public` (attachable) qui s'étend sur
tout le cluster et auquel tous les services exposés seront connectés.

Nginx Proxy Manager déployé comme stack Swarm, contraint sur le manager
(`node.role == manager`), avec deux volumes persistants (configuration et
certificats Let's Encrypt).

### 9. Piège — routing mesh ingress sur loopback

Premier obstacle : l'UI de NPM (port 81) était injoignable via un tunnel SSH
sur `localhost`. Le diagnostic a montré que le conteneur était parfaitement
sain (un `curl http://10.114.0.2:81` répondait `HTTP/1.1 200 OK`), mais qu'un
`curl http://localhost:81` restait bloqué jusqu'au timeout.

La cause : en mode `ingress` (le défaut Swarm), les ports publiés sont gérés
par le routing mesh IPVS, qui ne traite pas correctement le trafic loopback.

Solution : publier les ports de NPM en `mode: host` (syntaxe longue dans le
compose). Cela bind directement les ports sur le nœud hôte, sans passer par le
routing mesh. C'est de toute façon le mode recommandé pour un reverse proxy,
car il préserve l'IP source réelle des visiteurs au lieu de la masquer
derrière l'IP du load-balancer Swarm. Après redéploiement, `curl
http://localhost:81` répondait `200 OK`.

### 10. Premier service en HTTPS

Un service de test `whoami` (image `traefik/whoami`, deux replicas) a été
déployé sur le réseau `kennelo-public`, sans port exposé : seul NPM, sur le
même réseau overlay, peut l'atteindre par son nom de service `whoami`.

Dans NPM, un proxy host a été configuré pour `test.kennelo.fr` →
`http://whoami:80`, avec demande d'un certificat Let's Encrypt.

Première tentative en échec, avec un message clair dans les logs : l'ACME
server refusait l'email `admin@example.com` (email par défaut de NPM, jamais
modifié). Le domaine `example.com` est réservé et rejeté par Let's Encrypt.
Après mise à jour de l'email du compte avec une adresse valide, le certificat
a été émis en quelques secondes.

Validation finale : `https://test.kennelo.fr` répond avec un cadenas valide et
sert la page whoami. Les en-têtes confirment que la chaîne complète fonctionne :
`X-Forwarded-Proto: https` (terminaison TLS par NPM), et `X-Forwarded-For` /
`X-Real-Ip` portant la vraie IP du client (preuve que le mode host préserve
bien l'IP source). La répartition entre les deux replicas est observable en
rechargeant la page (le hostname du conteneur alterne).

Après validation, le service de test et son proxy host ont été supprimés pour
laisser le cluster propre.

## Validation

À la fin de la semaine :

- Trois nœuds Swarm Ready, Docker 28.5.1, communication via VPC privé
- Serveurs durcis : SSH par clé uniquement, root désactivé, UFW + fail2ban
- DNS géré par Cloudflare, onze enregistrements vers le manager, mails
  préservés
- Reverse proxy NPM opérationnel avec émission de certificats Let's Encrypt
- Chaîne complète validée de bout en bout : DNS → manager → NPM (TLS) →
  réseau overlay → service répliqué → HTTPS

## Incidents et leçons

- **fail2ban** : auto-bannissement, récupération par console DigitalOcean,
  ajout d'une whitelist. À automatiser en Ansible.
- **Docker 29** : pin sur 28.5.1 pour éviter les bugs Swarm connus.
- **Routing mesh** : `mode: host` pour le reverse proxy.
- **Email Let's Encrypt** : ne jamais laisser `admin@example.com`.
- **Console DigitalOcean** : éditer avec `nano`, pas de heredoc collé.

## Prochaine étape (Semaines 5-6)

- Déploiement des services stateful (PostgreSQL, Redis, MinIO) comme stacks
  Swarm sur le manager, avec volumes persistants et secrets Docker
- Déploiement de l'API Laravel et du Web Next.js depuis les images GHCR, avec
  authentification du cluster au registry privé
- Configuration des proxy hosts définitifs (api, ws, cdn, web) avec
  certificats Let's Encrypt
- Mise en place du multi-domaine (kennelo.fr / .com) et de la configuration
  i18n par domaine
