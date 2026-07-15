# Kennelo

**Kennelo** est une plateforme de garde d'animaux (_pet boarding_) qui met en relation des propriétaires d'animaux avec des établissements et des hôtes de confiance. L'application couvre la recherche et la réservation de gardes, la gestion des animaux, la messagerie propriétaire ↔ hôte, les paiements (Stripe), et un espace complet de gestion pour les établissements hôtes.

Le projet est un **monorepo pnpm** orchestré par **Turborepo**, partagé entre une application web/mobile et une API backend Laravel.

---

## Sommaire

- [Stack technique](#stack-technique)
- [Structure du monorepo](#structure-du-monorepo)
- [Prérequis](#prérequis)
- [Démarrage](#démarrage)
- [Scripts principaux](#scripts-principaux)
- [Architecture](#architecture)
- [Internationalisation](#internationalisation-i18n)
- [Mobile (Capacitor)](#mobile-capacitor)
- [Tests](#tests)
- [Conventions](#conventions)

---

## Stack technique

| Domaine     | Technologies                                                      |
| ----------- | ----------------------------------------------------------------- |
| Frontend    | Next.js 16 (App Router), React 19, Tailwind v4 (OKLCH, dark mode) |
| UI          | Shadcn UI / Radix, CVA, icônes custom dual-tone Kennelo           |
| Formulaires | react-hook-form + Zod (`zodResolver`)                             |
| Data        | TanStack React Query, DTO/Model + actions par domaine             |
| i18n        | next-intl (en, fr, ar) — support RTL                              |
| Paiements   | Stripe (Connect, Elements)                                        |
| Temps réel  | Laravel Echo + Pusher                                             |
| Mobile      | Capacitor (iOS / Android) — export statique                       |
| Backend     | Laravel (PHP) — dossier `api/`                                    |
| Outillage   | Turborepo, pnpm, TypeScript, ESLint, Prettier, Playwright         |

## Structure du monorepo

```
kennelo/
├── apps/
│   └── web/                  # App Next.js (web + mobile via Capacitor)
├── packages/
│   ├── ui/                   # Composants partagés Shadcn + icônes dual-tone
│   ├── modules/              # Domaines métier (DTO, models, actions, validateurs Zod)
│   ├── common/               # Client HTTP (api), helper JWT, interface storage
│   └── translations/         # Dictionnaires i18n (en, fr, ar) + next-intl
└── api/                      # Backend Laravel (ne pas modifier sauf demande explicite)
```

| Workspace               | Rôle                                                 |
| ----------------------- | ---------------------------------------------------- |
| `apps/web`              | Frontend web + mobile                                |
| `packages/ui`           | Librairie de composants partagés + système d'icônes  |
| `packages/modules`      | Modules domaine en CQRS-lite (DTO → Model → actions) |
| `packages/common`       | Client `api`, JWT, abstraction de stockage           |
| `packages/translations` | Traductions et configuration next-intl               |
| `api/`                  | API Laravel — source de vérité des données           |

### Domaines métier (`packages/modules`)

`users` · `bookings` · `pets` · `establishments` · `conversations` · `address`

Chaque domaine suit une architecture CQRS-lite :

```
src/<domain>/
├── models/
│   ├── dtos/<name>.dto.ts       # type snake_case, matche l'API
│   └── <name>.model.ts          # class camelCase, constructeur privé, static from()
├── actions/
│   ├── commands/<verb>-<noun>.ts  # écriture (POST / PUT / DELETE)
│   └── queries/get-<noun>.ts      # lecture (GET)
├── validators/<name>.schema.ts  # schéma Zod + type inféré
└── services/<name>.service.ts   # utilitaires stateful (tokens, cache…)
```

## Prérequis

- **Node.js** ≥ 20
- **pnpm** 10.4.1 (`packageManager` figé dans `package.json`)
- Une instance de l'API Laravel accessible (par défaut `http://localhost:8000/api`)

## Démarrage

```bash
# 1. Installer les dépendances (à la racine)
pnpm install

# 2. Construire les dictionnaires de traduction
pnpm build:translations

# 3. Lancer le frontend web en dev
pnpm --filter web dev
```

Configurez l'URL de l'API via la variable d'environnement `NEXT_PUBLIC_API_URL` (défaut : `http://localhost:8000/api`).

## Scripts principaux

```bash
pnpm dev                                     # Tous les workspaces en dev
pnpm --filter web dev                        # Web uniquement
pnpm build                                   # Build complet (Turborepo)
pnpm lint                                    # Lint complet
pnpm format                                  # Prettier

pnpm --filter @workspace/translations build  # Rebuild des dictionnaires i18n
pnpm --filter @workspace/modules barrels     # Regen des barrels de modules
pnpm --filter web generate:routes            # Regen de lib/routes.ts

pnpm --filter web dev:mobile                 # Dev en mode Capacitor
pnpm --filter web build:mobile               # Build statique mobile
pnpm --filter web sync:ios                   # Sync vers Xcode
pnpm --filter web sync:android               # Sync vers Android Studio

pnpm test:e2e                                # Tests Playwright
```

## Architecture

### Flux de données (API → UI)

```
Laravel API (JSON snake_case)
   ↓  DTO type (snake_case, matche l'API)
   ↓  Model class (camelCase, constructeur privé, static from())
   ↓  Action (appelle api, mappe DTO → Model)
   ↓  Hook React (useAsyncState / React Query — loading & error)
   ↓  Composant (rend les données via translations)
```

### Flux de formulaire (UI → API)

```
Zod Schema (camelCase)
   ↓  zodResolver() + useForm<InputType>()
   ↓  InputController (Field + erreur)
   ↓  handleSubmit → onSubmit
   ↓  useAsyncState().execute(() => action(data))
   ↓  Action (mappe camelCase → snake_case → api.post())
```

### Client API (`@workspace/common`)

```typescript
import { api } from "@workspace/common";

api.get<ResponseDto>("/endpoint", { queryParam: "value" });
api.post<ResponseDto>("/endpoint", { body_field: "value" });
api.put<ResponseDto>("/endpoint", { body_field: "value" });
api.delete<void>("/endpoint");
```

Le client gère automatiquement : le Bearer token (storage natif Capacitor ou cookies web), l'unwrap de l'enveloppe Laravel `{ data: ... }`, la détection de `FormData`, et les erreurs de validation via `.fieldErrors`.

### Routing Next.js

L'application utilise l'App Router avec des routes préfixées par locale (`/[locale]/…`) et des route groups par contexte de layout :

| Route group  | Usage                                             |
| ------------ | ------------------------------------------------- |
| `(auth)/`    | Login, register, mot de passe — non authentifié   |
| `(home)/`    | Home publique                                     |
| `(main)/`    | Pages authentifiées (pets, settings, explore…)    |
| `(hosting)/` | Gestion des établissements hôtes                  |
| `(host)/`    | Stepper de création d'établissement (become-host) |

Chaque page = **2 fichiers** : `page.tsx` (Server Component, `generateMetadata`) + `<name>-page.tsx` (Client Component interactif).

## Internationalisation (i18n)

Trois locales sont maintenues **en synchronie** : `en`, `fr`, `ar` (RTL). Les traductions vivent dans `packages/translations/locales/{en,fr,ar}/<category>/<file>.json`, où le chemin du fichier définit le namespace.

```typescript
const t = useTranslations(); // client
t("common.actions.save");
```

> Aucune string utilisateur en dur : tout passe par `useTranslations()` (client) ou `getTranslations()` (server). Après modification, rebuild : `pnpm build:translations`.

## Mobile (Capacitor)

L'app web est packagée en application native iOS/Android via **Capacitor**, en s'appuyant sur l'export statique de Next.js.

- `capacitor.config.ts` : `appId: "com.kennelo.app"`, `webDir: "out"`
- Build : `NEXT_PUBLIC_PLATFORM=mobile pnpm --filter web build:mobile`
- Le stockage runtime bascule entre Capacitor Preferences (natif) et cookies (web) via `lib/storage.ts`
- Détection de plateforme via le hook `usePlatform()` (`isNative`, `isIos`, `isAndroid`, `isBrowser`)

## Tests

Tests end-to-end avec **Playwright** :

```bash
pnpm test:e2e            # headless
pnpm test:e2e:ui         # mode UI
pnpm test:e2e:headed     # navigateur visible
```

## Conventions

Les règles détaillées sont documentées dans [`CLAUDE.md`](./CLAUDE.md). En résumé :

1. **Aucun commentaire dans le code** — les identifiants doivent être auto-explicatifs.
2. **Aucune string utilisateur en dur** — toujours via les traductions.
3. **Les 3 locales** (en, fr, ar) sont modifiées ensemble.
4. **Ne jamais modifier les fichiers auto-générés** (`lib/routes.ts`, barrels `index.ts`).
5. `type` pour les DTO/domaine (jamais `interface`), `Model.from(dto)` (jamais `new Model()`).
6. **Propriétés CSS logiques** (`start-*`, `ps-*`, `text-start`) pour le support RTL — jamais de physiques (`left-*`, `pl-*`).
7. **`cn()`** pour fusionner les classes Tailwind, **`data-slot`** sur chaque root de composant UI partagé.

| Élément             | Convention        | Exemple                             |
| ------------------- | ----------------- | ----------------------------------- |
| Dossiers / fichiers | kebab-case        | `create-booking/`, `login-form.tsx` |
| Type DTO            | `PascalCaseDto`   | `BookingDto`                        |
| Classe Model        | `PascalCaseModel` | `BookingModel`                      |
| Schéma Zod          | `camelCaseSchema` | `createBookingSchema`               |
| Action              | `camelCase`       | `createBooking`, `getBookings`      |
| Hook                | `use-kebab-case`  | `use-auth.tsx`                      |

## Infrastructure et déploiement

L'infrastructure (Docker Swarm, deux environnements — production DigitalOcean
et préproduction Hetzner — CI/CD par images versionnées, sauvegardes) est
documentée dans [infra/README.md](infra/README.md). Les procédures
d'exploitation (accès SSH, observabilité, déploiement, restauration) vivent
dans [infra/docs/](infra/docs/).
