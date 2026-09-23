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
        └── FakeSeeder.php       Orchestrateur, refuse de tourner en production
```

## Règles

- **Reference** est rejoué à chaque déploiement (`infra/scripts/migrate.sh`). Chaque seeder doit être idempotent.
- Deux comportements selon qui possède la donnée :
  - **Le fichier fait foi** (espèces, races, attributs) : le seeder synchronise la base avec le fichier (`updateOrCreate`).
  - **Le back-office fait foi** (rôles, réglages, plans, métiers) : le seeder crée seulement ce qui manque et ne modifie jamais l'existant.
- Rien de fictif dans Reference : ni Faker, ni factories (Faker n'est pas installé en production).
- Un nouveau seeder de référence s'ajoute dans `Reference/` et dans `ReferenceSeeder::run()` ; un seeder fictif dans `Fake/` et dans `FakeSeeder::run()`.

## Commandes

```bash
php artisan migrate:fresh --seed                                              # base complète (hors production : Reference + Fake)
php artisan db:seed --class='Database\Seeders\Reference\ReferenceSeeder'     # données de référence seules (production)
php artisan db:seed --class='Database\Seeders\Fake\FakeSeeder'                # données fictives seules
```
