# Seeders

```
database/
├── data/                        Données de référence (JSON), lues par les seeders Reference
│   ├── animal_types*.json       Espèces        ┐
│   ├── attribute_definitions*   Attributs      ├ *.translated.json générés par `php artisan data:translate`
│   ├── breeds/*.json            Races          ┘
│   └── professions.json         Catégories et métiers de départ (traductions saisies à la main)
└── seeders/
    ├── DatabaseSeeder.php       Point d'entrée : Reference partout, Fake hors production
    ├── Reference/               Données réelles, nécessaires au fonctionnement (tous environnements)
    │   ├── ReferenceSeeder.php  Orchestrateur, dans l'ordre des dépendances
    │   └── Concerns/            Outils partagés (lecture des fichiers de database/data)
    └── Fake/                    Données fictives (développement, démo), jamais en production
        ├── FakeSeeder.php       Orchestrateur, refuse de tourner en production
        └── DemoSeeder.php       Entreprise, activités réservables, clients, réservations, avis, messages
```

## Règles

- **Reference** est rejoué à chaque déploiement (`infra/scripts/migrate.sh`). Chaque seeder doit être idempotent.
- Deux comportements selon qui possède la donnée :
  - **Le fichier fait foi** (espèces, races, attributs) : le seeder synchronise la base avec le fichier (`updateOrCreate`).
  - **Le back-office fait foi** (rôles, plans, métiers). Les réglages ne sont pas seedés : leurs valeurs par défaut viennent de la config : le seeder crée seulement ce qui manque et ne modifie jamais l'existant.
- Rien de fictif dans Reference : ni Faker, ni factories (Faker n'est pas installé en production).
- Un nouveau seeder de référence s'ajoute dans `Reference/` et dans `ReferenceSeeder::run()` ; un seeder fictif dans `Fake/` et dans `FakeSeeder::run()`.

## Comptes de démonstration

Créés par `DemoSeeder`, tous avec le mot de passe `password` :

| Compte | Rôle |
| --- | --- |
| `admin@kennelo.test` | Back-office |
| `pro@kennelo.test` | Propriétaire de « Les Pattes du Lac » : une pension et un salon de toilettage à Annecy |
| `lea@kennelo.test` | Toiletteuse, employée du salon |
| `julie@kennelo.test` | Pet-sitter à Lyon (entreprise individuelle) |
| `camille@kennelo.test` | Cliente : Rex et Mina, un séjour terminé et noté, un en cours, un à venir, un rendez-vous, une conversation |
| `hugo@kennelo.test` | Client : une demande de séjour en attente |

Les dates sont relatives au jour de l'amorçage : relancer `migrate:fresh --seed` remet les séjours « en cours » et « à venir » à jour.

## Commandes

```bash
php artisan migrate:fresh --seed                                              # base complète (hors production : Reference + Fake)
php artisan db:seed --class='Database\Seeders\Reference\ReferenceSeeder'     # données de référence seules (production)
php artisan db:seed --class='Database\Seeders\Fake\FakeSeeder'                # données fictives seules
```
