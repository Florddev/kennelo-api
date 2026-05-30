// NOTE: This file is auto-generated
// Don't modify manually - your changes will be overwritten
// To regenerate it: pnpm --filter web generate:routes

import { buildRoute } from "./config/routes.config";

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

type HostingCalendarParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentAvailabilitiesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentBookingsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentCapacitiesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentCollaboratorsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentInvoicesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentDetailParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type EstablishmentPaymentParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type MyEstablishmentsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HostingMessagesParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HostingNowParams = {
    locale?: string | number;
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

type ExploreResultsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HostBookParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type HostDetailParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type MessagesParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetEditGeneralParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetEditHealthParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetEditPageParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetEditPersonalityParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetEditPhotosParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type PetDetailsParams = {
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

type ProfileParams = {
    locale?: string | number;
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

type SettingsParams = {
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

type RootPageParams = {
    search_params?: Record<string, string | number | boolean>;
};

function Login(params?: LoginParams): string {
    return buildRoute("/[locale]/login", params);
}

function Register(params?: RegisterParams): string {
    return buildRoute("/[locale]/register", params);
}

function Home(params?: HomeParams): string {
    return buildRoute("/[locale]", params);
}

function HostingCalendar(params?: HostingCalendarParams): string {
    return buildRoute("/[locale]/hosting/calendar", params);
}

function EstablishmentAvailabilities(params: EstablishmentAvailabilitiesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/availabilities", params);
}

function EstablishmentBookings(params: EstablishmentBookingsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/bookings", params);
}

function EstablishmentCapacities(params: EstablishmentCapacitiesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/capacities", params);
}

function EstablishmentCollaborators(params: EstablishmentCollaboratorsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/collaborators", params);
}

function EstablishmentInvoices(params: EstablishmentInvoicesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/invoices", params);
}

function EstablishmentDetail(params: EstablishmentDetailParams): string {
    return buildRoute("/[locale]/hosting/host/[id]", params);
}

function EstablishmentPayment(params: EstablishmentPaymentParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/payment", params);
}

function MyEstablishments(params?: MyEstablishmentsParams): string {
    return buildRoute("/[locale]/hosting/host", params);
}

function HostingMessages(params?: HostingMessagesParams): string {
    return buildRoute("/[locale]/hosting/messages", params);
}

function HostingNow(params?: HostingNowParams): string {
    return buildRoute("/[locale]/hosting/now", params);
}

function BecomeHost(params?: BecomeHostParams): string {
    return buildRoute("/[locale]/become-host", params);
}

function Explore(params?: ExploreParams): string {
    return buildRoute("/[locale]/explore", params);
}

function ExploreResults(params?: ExploreResultsParams): string {
    return buildRoute("/[locale]/explore/results", params);
}

function HostBook(params: HostBookParams): string {
    return buildRoute("/[locale]/host/[id]/book", params);
}

function HostDetail(params: HostDetailParams): string {
    return buildRoute("/[locale]/host/[id]", params);
}

function Messages(params?: MessagesParams): string {
    return buildRoute("/[locale]/messages", params);
}

function PetEditGeneral(params: PetEditGeneralParams): string {
    return buildRoute("/[locale]/pets/[id]/edit/general", params);
}

function PetEditHealth(params: PetEditHealthParams): string {
    return buildRoute("/[locale]/pets/[id]/edit/health", params);
}

function PetEditPage(params: PetEditPageParams): string {
    return buildRoute("/[locale]/pets/[id]/edit", params);
}

function PetEditPersonality(params: PetEditPersonalityParams): string {
    return buildRoute("/[locale]/pets/[id]/edit/personality", params);
}

function PetEditPhotos(params: PetEditPhotosParams): string {
    return buildRoute("/[locale]/pets/[id]/edit/photos", params);
}

function PetDetails(params: PetDetailsParams): string {
    return buildRoute("/[locale]/pets/[id]", params);
}

function NewPet(params?: NewPetParams): string {
    return buildRoute("/[locale]/pets/new", params);
}

function MyPets(params?: MyPetsParams): string {
    return buildRoute("/[locale]/pets", params);
}

function Profile(params?: ProfileParams): string {
    return buildRoute("/[locale]/profile", params);
}

function MyProfileAbout(params?: MyProfileAboutParams): string {
    return buildRoute("/[locale]/settings/about", params);
}

function MyProfileChangePassword(params?: MyProfileChangePasswordParams): string {
    return buildRoute("/[locale]/settings/change-password", params);
}

function Settings(params?: SettingsParams): string {
    return buildRoute("/[locale]/settings", params);
}

function MyProfileEmailPreferences(params?: MyProfileEmailPreferencesParams): string {
    return buildRoute("/[locale]/settings/preferences-email", params);
}

function MyProfilePreferencesNotification(params?: MyProfilePreferencesNotificationParams): string {
    return buildRoute("/[locale]/settings/preferences-notification", params);
}

function RootPage(params?: RootPageParams): string {
    return buildRoute("/", params);
}

export const routes = {
    Login,
    Register,
    Home,
    HostingCalendar,
    EstablishmentAvailabilities,
    EstablishmentBookings,
    EstablishmentCapacities,
    EstablishmentCollaborators,
    EstablishmentInvoices,
    EstablishmentDetail,
    EstablishmentPayment,
    MyEstablishments,
    HostingMessages,
    HostingNow,
    BecomeHost,
    Explore,
    ExploreResults,
    HostBook,
    HostDetail,
    Messages,
    PetEditGeneral,
    PetEditHealth,
    PetEditPage,
    PetEditPersonality,
    PetEditPhotos,
    PetDetails,
    NewPet,
    MyPets,
    Profile,
    MyProfileAbout,
    MyProfileChangePassword,
    Settings,
    MyProfileEmailPreferences,
    MyProfilePreferencesNotification,
    RootPage,
} as const;

export type RouteName = keyof typeof routes;
