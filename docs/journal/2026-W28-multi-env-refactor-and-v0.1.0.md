# Semaine 8 — Refactoring multi-environnement et bascule v0.1.0

## Contexte

Depuis la semaine 7, la production tourne de manière stable sur le cluster
Swarm, mais son déploiement reste artisanal : un script unique qui mélange
tous les services, des stacks aux valeurs codées en dur (domaines, tags
d'images, replicas), et des images taguées `:main` qui suivent silencieusement
la branche principale. Ce fonctionnement ne peut servir ni une préproduction
(les valeurs sont celles de la prod), ni un déploiement automatisé (rien ne
distingue ce qui doit être redéployé souvent de ce qui ne doit jamais l'être).

Cette semaine ouvre le plan multi-environnement, découpé en trois chantiers :
moderniser le déploiement de la prod existante (cette semaine), monter une
préproduction isolée sur un VPS dédié, puis automatiser la CI/CD via GitHub
Actions. Le principe directeur du plan : main = préprod déployée
automatiquement, tag git `vX.Y.Z` = release de production explicite.

## Objectif

- Externaliser dans des fichiers versionnés tout ce qui varie entre
  environnements, sans jamais y mettre de secret
- Découper le script de déploiement par cycle de vie des services
  (applicatif, plateforme, administration)
- Faire produire par la CI des images versionnées : `:preprod-latest` sur
  main, `:vX.Y.Z` immuable et pointeur `:prod` sur tag git
- Basculer la production sur la release v0.1.0 sans changement de binaire ni
  interruption de service
- Fournir l'outillage de déploiement manuel (Makefile) qui survivra au CD

## Durée effective

Une session de travail prolongée pour le refactoring, plus la fenêtre de
bascule opérée séparément.

## Réalisations

### 1. Configuration par environnement

Les valeurs qui varient entre prod et préprod vivent désormais dans
`infra/env/prod.env` et `infra/env/preprod.env`, versionnés, accompagnés d'un
`example.env` qui documente chaque variable. Les stacks applicatives (api,
web, reverb) référencent ces valeurs par interpolation (`${IMAGE_TAG}`,
`${APP_URL}`, `${API_REPLICAS}`…) ; les stacks plateforme et admin,
identiques entre environnements, restent intactes. Règle absolue posée dans
le template et le README : aucun secret dans ces fichiers, les mots de passe
et clés restent exclusivement dans les Docker Secrets.

Un `diff infra/env/prod.env infra/env/preprod.env` montre désormais en une
commande la totalité des différences entre les deux environnements — outil de
debug précieux et garde-fou contre la dérive de configuration.

### 2. Scripts de déploiement par cycle de vie

L'ancien `deploy.sh` déployait tout à chaque exécution et pullait encore un
tag d'image obsolète datant de la mise en place du cluster. Il est remplacé
par quatre scripts aux responsabilités nettes :

- `deploy-app.sh` (api, web, reverb) : le seul que le futur CD appellera, à
  chaque merge en préprod et à chaque release en prod
- `deploy-platform.sh` (proxy, postgres, redis, minio) : manuel, les deux
  environnements
- `deploy-admin.sh` (portainer, monitoring) : manuel, refuse tout
  environnement autre que la prod
- `deploy.sh` : orchestrateur manuel des trois, pour un setup initial ou du
  debug

Tous valident leurs entrées avant d'agir : environnement inconnu, fichier de
configuration manquant ou variable requise absente arrêtent le script avec un
message actionnable. Le `git pull` a été retiré des scripts : le checkout du
bon ref est la responsabilité de l'appelant, ce qui permet de déployer depuis
un HEAD détaché quand le manager de production est épinglé sur un tag.

### 3. CI d'images versionnées, build web par environnement

Le workflow de build produit désormais des artefacts distincts par
environnement et par release. Un push sur main publie `:preprod-latest` et
`:sha-xxx` ; un push de tag `v*.*.*` publie `:vX.Y.Z` (immuable), `:prod`
(pointeur vers la dernière release) et `:sha-xxx`. Les tags `:main` et
`:latest` ne sont plus produits : un tag dont le nom ne dit pas ce qu'il
contient est une source de confusion opérationnelle.

L'image web est buildée par environnement, car les variables `NEXT_PUBLIC_*`
sont figées dans le bundle client au moment du build. Plutôt que de les coder
en dur dans le workflow, elles sont dérivées des fichiers `infra/env/*.env` —
la même source de vérité que les scripts de déploiement. Cette stratégie de
build par environnement est cohérente avec les builds mobiles Capacitor,
distribués par nature en bundles statiques par environnement.

### 4. Bascule de la production sur la release v0.1.0

La production est passée des images `:main` aux images de release, sans
rebuild et sans interruption. Le détail de l'orchestration est documenté dans
la section « Point de bascule v0.1.0 » ci-dessous, pour être reproductible.

### 5. Outillage manuel et nettoyage

Un Makefile (cibles `deploy-preprod`, `deploy-prod TAG=vX.Y.Z`, `rollback`,
`status`) compose le checkout git et l'appel du bon script pour l'opérateur
humain, avec validation stricte du format de tag avant toute action. Le CD ne
l'utilisera pas : il fait son propre checkout puis appelle `deploy-app.sh`
directement. Le workflow one-shot de retag, qui n'avait qu'un rôle (créer
`v0.1.0` et `prod` sans rebuild), a été supprimé après usage : un
`workflow_dispatch` capable de déplacer des tags de release ne doit pas
survivre à sa raison d'être.

## Validation

- Rendu des stacks avec `prod.env` identique octet pour octet à l'ancien
  rendu en dur (`docker stack config`) : aucun changement de comportement
  avant la bascule
- URLs de build web dérivées des fichiers env identiques aux anciennes
  valeurs codées en dur du workflow
- Scripts testés hors cluster (shim docker) : chemins d'erreur et séquences
  complètes des deux environnements, propagation de `IMAGE_TAG` vérifiée
  jusqu'aux commandes `docker stack deploy`
- Digests des images retaguées vérifiés : `:v0.1.0` et `:prod` pointent le
  manifest exact de `:main`
- Après bascule : services sur image `:prod`, endpoints de santé web, API et
  WebSocket en 200

## Décisions et points techniques

- **Fichiers env versionnés, jamais de secret** : la configuration
  d'environnement est auditable dans git ; les secrets restent dans les
  Docker Secrets, et des motifs défensifs ont été ajoutés au `.gitignore`.
- **Build web par environnement** : imposé par les `NEXT_PUBLIC_*` inlinés au
  build, assumé pour rester cohérent avec la contrainte mobile. La
  configuration runtime reste une évolution possible si le besoin change
  (environnements éphémères, multi-région), notée comme réflexion ouverte.
- **Retag sans rebuild pour la première release** : rebuilder aurait produit
  un binaire différent de celui qui tournait ; copier le manifest garantit
  que `v0.1.0` désigne exactement les octets en production.
- **Pas de git dans les scripts de déploiement** : responsabilité unique,
  scripts utilisables par le CD, par un humain, ou depuis un HEAD détaché.
- **Rollback = redéploiement d'un ancien tag** : pas de mécanisme dédié,
  `make rollback TAG=vX.Y.Z` est un alias assumé de `deploy-prod`.

### Point de bascule v0.1.0

L'enjeu : faire exister une release versionnée avant que la CI ne cesse de
produire `:main`, sans jamais laisser la production sans image déployable.
Deux contraintes GitHub ont dicté l'ordre. Un workflow `workflow_dispatch`
n'est lançable que si son fichier existe sur la branche par défaut, donc le
retag ne pouvait précéder le merge. Et un push de tag exécute le workflow tel
qu'il existe au commit tagué : posé sur un commit d'avant le refactoring, le
tag git `v0.1.0` ne déclenche aucun build, donc aucun rebuild ne pouvait
écraser le retag manuel.

L'ordre exécuté, reproductible :

1. Noter le SHA de `origin/main` juste avant le merge (état pré-refactoring,
   futur support du tag git).
2. Merger la PR du refactoring. À partir d'ici et jusqu'à l'étape 7, règle
   d'or : ne rien lancer sur le manager de production. L'image `:main`
   existante reste intacte dans le registre, la prod continue de tourner.
3. Attendre que le workflow CI s'exécute au vert sur main (il produit
   `:preprod-latest`, sans rôle dans la bascule).
4. Lancer le workflow one-shot de retag (source `main`, cibles
   `v0.1.0,prod`) : copie de manifest sans rebuild pour les images api et
   web, avec inspection des tags créés dans le run pour vérifier les digests.
5. Poser le tag git `v0.1.0` sur le SHA noté en 1, avec un message qui
   explicite l'équivalence avec l'état pré-refactoring, puis le pousser.
6. Sur le manager : `git checkout main && git pull`. Rien ne bouge encore,
   les services tournent toujours sur `:main`.
7. `ENV=prod ./infra/scripts/deploy-app.sh` : rolling update qui remplace les
   conteneurs `:main` par des conteneurs `:prod` — même binaire, zéro
   interruption.
8. Vérifier : `docker service ps` montre l'image `:prod`, les endpoints de
   santé (web `/api/health`, API `/up`, WebSocket `/up`) répondent 200.
9. Documenter la bascule (le présent journal).

### Leçon apprise : la dette dormante révélée

La bascule a mis au jour un fait passé inaperçu : la production n'avait pas
été redéployée depuis environ un mois. L'image `:main` du registre, elle,
avait continué d'avancer à chaque merge (Next.js est par exemple passé de
16.2.3 à 16.2.6 entre le dernier déploiement et la bascule). Le retag a donc
fait « sauter » la production sur un mois de commits d'un coup, et un bug
applicatif dormant a émergé : une configuration Google OAuth incorrecte,
tracée dans l'issue #93 (code applicatif, hors périmètre infra).

Il faut lire cet incident comme une validation du nouveau système, pas comme
une faiblesse. L'ancien fonctionnement rendait cette dérive invisible par
construction : le tag `:main` avançait silencieusement pendant que la
production restait figée, et personne ne savait dire quel écart les séparait.
Avec les releases taguées, chaque déploiement de production devient une
décision explicite — un tag posé par un humain, un artefact immuable, un
historique — et l'écart entre main et la prod se lit dans `git log
v0.1.0..main`. La préproduction du chantier 2, déployée automatiquement à
chaque merge, aurait par ailleurs exposé le bug OAuth des semaines avant
qu'il n'atteigne un tag de production.

Même mécanique du côté du refactoring lui-même : en externalisant les
domaines dans les fichiers env, l'inventaire des valeurs en dur a révélé que
`cdn.kennelo.fr` — l'URL publique des médias — n'avait aucun hôte proxy dans
le reverse proxy de production. DNS et MinIO étaient corrects, mais les URLs
de médias étaient cassées côté client depuis le début, sans qu'aucun signal
ne le remonte. Le fix (hôtes proxy prod et préprod, certificats) est planifié
au chantier 2. Le refactoring propre révèle les incohérences qui existaient
avant lui — c'est une partie de sa valeur.

## Prochaine étape

- Documentation d'exploitation : README infra à jour (déploiement manuel,
  rollback) et section « Déploiement et environnements » dans la
  documentation d'observabilité
- Chantier 2 : VPS préprod dédié (provisioning, secrets propres, stacks en
  replicas 1, DNS `preprod.kennelo.fr`, `api.preprod`, `ws.preprod`,
  `cdn.preprod`), et au passage le fix de l'hôte proxy `cdn.kennelo.fr` en
  production
- Chantier 3 : workflows de déploiement automatisé (préprod sur merge, prod
  sur tag), clés SSH de déploiement dédiées par environnement
