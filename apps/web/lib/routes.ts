// NOTE: This file is auto-generated
// Don't modify manually - your changes will be overwritten
// To regenerate it: pnpm --filter web generate:routes

import { buildRoute } from "./config/routes.config";

type RootPageParams = {
    search_params?: Record<string, string | number | boolean>;
};

type LoginParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type RegisterParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HomeParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyEstablishmentsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentDetailParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type BecomeHostParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ExploreParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ExploreBookParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ExploreDetailParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type NewPetParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyPetsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetDetailsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type MyProfileAboutParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyProfileChangePasswordParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyProfileEmailPreferencesParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyProfilePreferencesNotificationParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

function RootPage(params?: RootPageParams): string {
    return buildRoute("/", params);
}

function Login(params?: LoginParams): string {
    return buildRoute("/[locale]/login", params);
}

function Register(params?: RegisterParams): string {
    return buildRoute("/[locale]/register", params);
}

function Home(params?: HomeParams): string {
    return buildRoute("/[locale]", params);
}

function MyEstablishments(params?: MyEstablishmentsParams): string {
    return buildRoute("/[locale]/hosting/host", params);
}

function EstablishmentDetail(params: EstablishmentDetailParams): string {
    return buildRoute("/[locale]/hosting/host/[id]", params);
}

function BecomeHost(params?: BecomeHostParams): string {
    return buildRoute("/[locale]/become-host", params);
}

function Explore(params?: ExploreParams): string {
    return buildRoute("/[locale]/explore", params);
}

function ExploreBook(params: ExploreBookParams): string {
    return buildRoute("/[locale]/explore/[id]/book", params);
}

function ExploreDetail(params: ExploreDetailParams): string {
    return buildRoute("/[locale]/explore/[id]", params);
}

function NewPet(params?: NewPetParams): string {
    return buildRoute("/[locale]/pets/new", params);
}

function MyPets(params?: MyPetsParams): string {
    return buildRoute("/[locale]/pets", params);
}

function PetDetails(params: PetDetailsParams): string {
    return buildRoute("/[locale]/pets/[id]", params);
}

function MyProfileAbout(params?: MyProfileAboutParams): string {
    return buildRoute("/[locale]/settings/about", params);
}

function MyProfileChangePassword(params?: MyProfileChangePasswordParams): string {
    return buildRoute("/[locale]/settings/change-password", params);
}

function MyProfileEmailPreferences(params?: MyProfileEmailPreferencesParams): string {
    return buildRoute("/[locale]/settings/preferences-email", params);
}

function MyProfilePreferencesNotification(params?: MyProfilePreferencesNotificationParams): string {
    return buildRoute("/[locale]/settings/preferences-notification", params);
}

export const routes = {
    RootPage,
    Login,
    Register,
    Home,
    MyEstablishments,
    EstablishmentDetail,
    BecomeHost,
    Explore,
    ExploreBook,
    ExploreDetail,
    NewPet,
    MyPets,
    PetDetails,
    MyProfileAbout,
    MyProfileChangePassword,
    MyProfileEmailPreferences,
    MyProfilePreferencesNotification,
} as const;

export type RouteName = keyof typeof routes;
