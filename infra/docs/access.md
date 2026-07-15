# Accès au cluster Kennelo

Mémo des commandes d'accès SSH et tunnels au cluster Docker Swarm.

Les alias SSH (`kennelo-manager`, `kennelo-worker-1`, `kennelo-worker-2`) et
les alias shell de tunnel sont définis côté poste de développement (Mac), pas
sur les serveurs. Sur une nouvelle machine, il faut donc recréer ces alias
(voir la section « Configuration » en bas).

## Connexions SSH aux nœuds

Quatre nœuds : le cluster de production (trois nœuds DigitalOcean) et le VPS
de préproduction (Hetzner). On se connecte toujours avec l'utilisateur
`kennelo` (jamais `root` : une connexion root est automatiquement bannie par
fail2ban).

| Commande                       | Cible                            | Rôle                                                    |
| ------------------------------ | -------------------------------- | ------------------------------------------------------- |
| `ssh kennelo@kennelo-manager`  | swarm-manager (46.101.124.234)   | Nœud manager prod (orchestration + services data)       |
| `ssh kennelo@kennelo-worker-1` | swarm-worker-1 (104.248.252.27)  | Nœud worker prod 1 (services applicatifs)               |
| `ssh kennelo@kennelo-worker-2` | swarm-worker-2 (165.227.136.219) | Nœud worker prod 2 (services applicatifs)               |
| `ssh kennelo@kennelo-preprod`  | preprod-manager (167.233.60.73)  | VPS préproduction Hetzner (Swarm mono-nœud, tout-en-un) |

Le manager héberge l'orchestration Swarm et les services stateful (PostgreSQL,
Redis, MinIO, le reverse proxy). Les workers font tourner les services
applicatifs (API, web, Reverb).

La quasi-totalité de l'administration (déploiements, logs, état des services)
se fait depuis le manager, car c'est le seul nœud d'où les commandes
`docker service` / `docker stack` fonctionnent.

## Tunnels vers les interfaces d'administration

Aucune interface d'administration n'est exposée publiquement. Toutes sont
publiées en local sur le manager et bloquées au public par le pare-feu ; on y
accède exclusivement par tunnel SSH. C'est un choix de sécurité cohérent :
les composants capables de piloter le cluster ou d'exposer des données internes
ne présentent aucune surface d'attaque publique.

| Alias              | Tunnel                       | Interface                           | Accès navigateur      |
| ------------------ | ---------------------------- | ----------------------------------- | --------------------- |
| `npmkennelo`       | port 81 → `localhost:8181`   | Nginx Proxy Manager (reverse proxy) | http://localhost:8181 |
| `portainerkennelo` | port 9000 → `localhost:9000` | Portainer (pilotage du cluster)     | http://localhost:9000 |
| `grafanakennelo`   | port 3000 → `localhost:3000` | Grafana (dashboards de métriques)   | http://localhost:3000 |
| `promkennelo`      | port 9090 → `localhost:9090` | Prometheus (collecte de métriques)  | http://localhost:9090 |

Chaque alias ouvre un tunnel SSH vers le manager. Tant que le terminal reste
ouvert, l'accès local fonctionne ; fermer le terminal ferme le tunnel.

## Back-office d'administration

Le back-office métier (gestion des utilisateurs, professionnels, prospection,
audit) n'utilise pas de tunnel : il est exposé publiquement sur
`https://admin.kennelo.fr` (prod) et `https://admin.preprod.kennelo.fr`
(préprod), via un proxy host NPM pointant vers `back-office_back-office:3000`
sur chaque environnement. L'accès est réservé aux comptes ayant le rôle
`admin`, avec une protection multi-couche (proxy Next.js server-side, hook
client, middleware API) détaillée dans `observability.md`, section
« Back-office d'administration ».

## Configuration (à recréer sur une nouvelle machine)

### Alias SSH

Dans `~/.ssh/config` :

```
Host kennelo-manager
    HostName 46.101.124.234
    User kennelo

Host kennelo-worker-1
    HostName 104.248.252.27
    User kennelo

Host kennelo-worker-2
    HostName 165.227.136.219
    User kennelo

Host kennelo-preprod
    HostName 167.233.60.73
    User kennelo
```

### Alias des tunnels

Dans `~/.zshrc` :

```
alias npmkennelo='ssh -L 8181:localhost:81 kennelo@kennelo-manager'
alias portainerkennelo='ssh -L 9000:localhost:9000 kennelo@kennelo-manager'
alias grafanakennelo='ssh -L 3000:localhost:3000 kennelo@kennelo-manager'
alias promkennelo='ssh -L 9090:localhost:9090 kennelo@kennelo-manager'
```

Recharger ensuite avec `source ~/.zshrc`.

## Notes

- L'accès SSH suppose que la clé publique correspondante est présente dans les
  clés autorisées du nœud. Pour donner l'accès à un nouveau membre de l'équipe,
  ajouter sa clé publique sur les nœuds concernés.
- Deux clés SSH dédiées au déploiement continu (distinctes des clés humaines)
  sont autorisées sur le manager prod et sur la préproduction ; leurs clés
  privées vivent dans les GitHub Secrets (`SSH_PRIVATE_KEY_PROD`,
  `SSH_PRIVATE_KEY_PREPROD`). Révocables indépendamment en retirant la clé
  publique des `authorized_keys` de la cible.
- Le manager prod possède sa propre clé (`~/.ssh/id_ed25519`), autorisée sur
  les deux workers, pour le rebond manager→worker utilisé par le workflow de
  déploiement prod (vérification des migrations dans un container api).
- Ne jamais se connecter en `root` : fail2ban bannit automatiquement ces
  tentatives.
- Les interfaces d'administration accessibles par tunnel (NPM, Portainer,
  Grafana, Prometheus) ont leurs ports bloqués au public par le pare-feu. Voir
  `observability.md` pour le détail de la sécurisation des ports.

## Propriété des comptes de services

En attendant les adresses de service (`admin@kennelo.fr`…), les comptes des
services liés à l'infrastructure sont portés par des comptes personnels,
documentés ici pour le bus factor :

| Service                                   | Compte             | Rôle                             |
| ----------------------------------------- | ------------------ | -------------------------------- |
| Cloudflare (zones DNS kennelo.\*)         | compte de Florian  | DNS, certificats d'origine       |
| Cloudflare R2 (copie externe des backups) | compte de Thami    | bucket kennelo-backups           |
| DigitalOcean (cluster prod + Spaces)      | compte de Thami    | droplets, bucket kennelo-backups |
| Hetzner (VPS préproduction)               | compte de Thami    | preprod-manager                  |
| healthchecks.io (supervision backups)     | developer@thami.fr | check kennelo-backup-prod        |
