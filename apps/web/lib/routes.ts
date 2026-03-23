// NOTE: This file is auto-generated
// Don't modify manually - your changes will be overwritten
// To regenerate it: pnpm --filter web generate:routes

import { buildRoute } from "./config/routes.config";

type BecomeHostParams = {
    locale?: string | number;
};

type HostDetailsParams = {
    locale?: string | number;
    uuid: string;
};

type HomeParams = {
    locale?: string | number;
};

type LoginParams = {
    locale?: string | number;
};

type RegisterParams = {
    locale?: string | number;
};

type MyEstablishmentsParams = {
    locale?: string | number;
};

type EstablishmentDetailParams = {
    locale?: string | number;
    id: string | number;
};

type MyPetsParams = {
    locale?: string | number;
};

type PetDetailsParams = {
    locale?: string | number;
    id: string | number;
};

type MyProfileAboutParams = {
    locale?: string | number;
};

type MyProfileChangePasswordParams = {
    locale?: string | number;
};

type MyProfileEmailPreferencesParams = {
    locale?: string | number;
};

type MyProfilePreferencesNotificationParams = {
    locale?: string | number;
};

function RootPage(): string {
    return "/";
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
