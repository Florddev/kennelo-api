# Accès au cluster Kennelo

Mémo des commandes d'accès SSH et tunnels au cluster Docker Swarm.

Les alias SSH (`kennelo-manager`, `kennelo-worker-1`, `kennelo-worker-2`) et
l'alias shell `npmkennelo` sont définis côté poste de développement (Mac), pas
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

## Tunnel vers l'administration du reverse proxy (NPM)

| Commande     | Effet                                                                                                                |
| ------------ | -------------------------------------------------------------------------------------------------------------------- |
| `npmkennelo` | Ouvre un tunnel SSH qui expose le port 81 du manager (interface d'admin de Nginx Proxy Manager) sur `localhost:8181` |

Détail de ce que fait l'alias :

```
ssh -L 8181:localhost:81 kennelo@kennelo-manager
```

Une fois le tunnel ouvert, l'interface d'administration de NPM est accessible
dans le navigateur à l'adresse : http://localhost:8181

L'interface d'administration de NPM n'est **pas** exposée publiquement (pas de
sous-domaine). C'est un choix de sécurité : ce composant contrôle tout le
routage et les certificats du cluster, donc il reste accessible uniquement par
tunnel SSH, sans surface d'attaque exposée. Tant que le tunnel est ouvert
(terminal laissé actif), l'accès local fonctionne ; fermer le terminal ferme
le tunnel.

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

### Alias du tunnel NPM

Dans `~/.zshrc` :

```
alias npmkennelo='ssh -L 8181:localhost:81 kennelo@kennelo-manager'
```

Recharger ensuite avec `source ~/.zshrc`.

## Notes

- L'accès SSH suppose que la clé publique correspondante est présente dans les
  clés autorisées du nœud. Pour donner l'accès à un nouveau membre de l'équipe,
  ajouter sa clé publique sur les nœuds concernés.
- Ne jamais se connecter en `root` : fail2ban bannit automatiquement ces
  tentatives.
