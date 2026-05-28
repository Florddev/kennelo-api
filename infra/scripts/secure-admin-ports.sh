#!/bin/bash
#
# Restreint l'accès aux ports d'administration publiés par Docker (Portainer,
# Dozzle, etc.) à la loopback uniquement, via la chaîne DOCKER-USER.
#
# Docker contourne UFW en écrivant directement dans iptables. La chaîne
# DOCKER-USER est le point d'insertion prévu pour les règles utilisateur :
# elle est évaluée avant les règles de publication de Docker.
#
# Ces interfaces d'admin ne doivent être accessibles que via un tunnel SSH
# (trafic loopback sur le manager), jamais depuis l'extérieur.
#
# Le script est idempotent : il supprime d'éventuelles règles existantes pour
# chaque port avant de les recréer, donc il peut être rejoué sans accumuler de
# doublons (au boot, ou après un redémarrage du démon Docker).

set -euo pipefail

# Ports d'administration à restreindre à la loopback.
ADMIN_PORTS=(9000 9090 3000)

for port in "${ADMIN_PORTS[@]}"; do
    # Nettoyer les règles existantes pour ce port (idempotence).
    while iptables -C DOCKER-USER -p tcp --dport "$port" -s 127.0.0.1 -j ACCEPT 2>/dev/null; do
        iptables -D DOCKER-USER -p tcp --dport "$port" -s 127.0.0.1 -j ACCEPT
    done
    while iptables -C DOCKER-USER -p tcp --dport "$port" -j DROP 2>/dev/null; do
        iptables -D DOCKER-USER -p tcp --dport "$port" -j DROP
    done

    # Réinsérer dans le bon ordre : ACCEPT loopback en tête, puis DROP le reste.
    iptables -I DOCKER-USER -p tcp --dport "$port" -j DROP
    iptables -I DOCKER-USER -p tcp --dport "$port" -s 127.0.0.1 -j ACCEPT

    echo "Port $port restreint à la loopback (DROP externe, ACCEPT 127.0.0.1)."
done
