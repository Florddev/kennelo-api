// NOTE: This file is auto-generated
// Don't modify manually - your changes will be overwritten
// To regenerate it: pnpm --filter web generate:routes

import { buildRoute } from "./config/routes.config";

type RootPageParams = {
    search_params?: Record<string, string | number | boolean>;
};

type BecomeHostParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HostDetailsParams = {
    locale?: string | number;
    uuid: string;
    search_params?: Record<string, string | number | boolean>;
};

type HomeParams = {
    locale?: string | number;
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

type BecomeHostV2Params = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyEstablishmentsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentDetailParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyPetsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetDetailsParams = {
    locale?: string | number;
    id: string | number;
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

function BecomeHost(params?: BecomeHostParams): string {
    return buildRoute("/[locale]/become-host", params);
}

function HostDetails(params: HostDetailsParams): string {
    return buildRoute("/[locale]/host/[uuid]", params);
}

function Home(params?: HomeParams): string {
    return buildRoute("/[locale]", params);
}

function Login(params?: LoginParams): string {
    return buildRoute("/[locale]/s/accounts/login", params);
}

function Register(params?: RegisterParams): string {
    return buildRoute("/[locale]/s/accounts/register", params);
}

function BecomeHostV2(params?: BecomeHostV2Params): string {
    return buildRoute("/[locale]/s/app/establishments/become-host-v2", params);
}

function MyEstablishments(params?: MyEstablishmentsParams): string {
    return buildRoute("/[locale]/s/app/establishments", params);
}

function EstablishmentDetail(params: EstablishmentDetailParams): string {
    return buildRoute("/[locale]/s/app/establishments/[id]", params);
}

function MyPets(params?: MyPetsParams): string {
    return buildRoute("/[locale]/s/my/pets", params);
}

function PetDetails(params: PetDetailsParams): string {
    return buildRoute("/[locale]/s/my/pets/[id]", params);
}

function MyProfileAbout(params?: MyProfileAboutParams): string {
    return buildRoute("/[locale]/s/my/profile/about", params);
}

function MyProfileChangePassword(params?: MyProfileChangePasswordParams): string {
    return buildRoute("/[locale]/s/my/profile/change-password", params);
}

function MyProfileEmailPreferences(params?: MyProfileEmailPreferencesParams): string {
    return buildRoute("/[locale]/s/my/profile/preferences-email", params);
}

function MyProfilePreferencesNotification(params?: MyProfilePreferencesNotificationParams): string {
    return buildRoute("/[locale]/s/my/profile/preferences-notification", params);
}

export const routes = {
    RootPage,
    BecomeHost,
    HostDetails,
    Home,
    Login,
    Register,
    BecomeHostV2,
    MyEstablishments,
    EstablishmentDetail,
    MyPets,
    PetDetails,
    MyProfileAbout,
    MyProfileChangePassword,
    MyProfileEmailPreferences,
    MyProfilePreferencesNotification,
} as const;

export type RouteName = keyof typeof routes;
