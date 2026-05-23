# Accès au cluster Kennelo

Mémo des commandes d'accès SSH et tunnels au cluster Docker Swarm.

Les alias SSH (`kennelo-manager`, `kennelo-worker-1`, `kennelo-worker-2`) et
les alias shell de tunnel sont définis côté poste de développement (Mac), pas
sur les serveurs. Sur une nouvelle machine, il faut donc recréer ces alias
(voir la section « Configuration » en bas).

## Connexions SSH aux nœuds

Le cluster est composé de trois nœuds DigitalOcean. On se connecte toujours
avec l'utilisateur `kennelo` (jamais `root` : une connexion root est
automatiquement bannie par fail2ban).

| Commande                       | Cible                            | Rôle                                                    |
| ------------------------------ | -------------------------------- | ------------------------------------------------------- |
| `ssh kennelo@kennelo-manager`  | swarm-manager (46.101.124.234)   | Nœud manager du cluster (orchestration + services data) |
| `ssh kennelo@kennelo-worker-1` | swarm-worker-1 (104.248.252.27)  | Nœud worker 1 (services applicatifs)                    |
| `ssh kennelo@kennelo-worker-2` | swarm-worker-2 (165.227.136.219) | Nœud worker 2 (services applicatifs)                    |

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
- Ne jamais se connecter en `root` : fail2ban bannit automatiquement ces
  tentatives.
- Les interfaces d'administration accessibles par tunnel (NPM, Portainer,
  Grafana, Prometheus) ont leurs ports bloqués au public par le pare-feu. Voir
  `docs/observability.md` pour le détail de la sécurisation des ports.
