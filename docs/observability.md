# Observabilité et exploitation du cluster

Ce document décrit les outils d'administration du cluster (Portainer), leur
sécurisation et les procédures d'exploitation associées.

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
doublons).

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
