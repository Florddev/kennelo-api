// NOTE: This file is auto-generated
// Don't modify manually - your changes will be overwritten
// To regenerate it: pnpm --filter web generate:routes

import { buildRoute } from "./config/routes.config";

type RootPageParams = {
    search_params?: Record<string, string | number | boolean>;
};

type ForgotPasswordParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type LoginParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MagicLinkParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MagicLinkVerifyParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type RegisterParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ResetPasswordParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type VerifyEmailParams = {
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

type MyActivitiesParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type TempParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityBookingsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityInvoicesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityOverviewParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityDetailsParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityAvailabilitiesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityCollaboratorsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityCyclesParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivitySettingsInformationsParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivitySettingsParams = {
    locale?: string | number;
    id: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityPaymentParams = {
    locale?: string | number;
    id: string;
    search_params?: Record<string, string | number | boolean>;
};

type ActivityServicesParams = {
    locale?: string | number;
    id: string;
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

type HostingScanParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type HostingScanResultParams = {
    locale?: string | number;
    microchip: string;
    search_params?: Record<string, string | number | boolean>;
};

type HostingSubscriptionParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type CguParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type CgvParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ConfidentialiteParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type ContactParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type BecomeHostParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type BookingDetailParams = {
    locale?: string | number;
    id: string;
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

type FavoritesParams = {
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

type NotificationsParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type LivePetParams = {
    locale?: string | number;
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
    id: string;
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

type PaymentMethodsPageParams = {
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

type SettingsScannersParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

type MyProfileTwoFactorParams = {
    locale?: string | number;
    search_params?: Record<string, string | number | boolean>;
};

function RootPage(params?: RootPageParams): string {
    return buildRoute("/", params);
}

function ForgotPassword(params?: ForgotPasswordParams): string {
    return buildRoute("/[locale]/forgot-password", params);
}

function Login(params?: LoginParams): string {
    return buildRoute("/[locale]/login", params);
}

function MagicLink(params?: MagicLinkParams): string {
    return buildRoute("/[locale]/magic-link", params);
}

function MagicLinkVerify(params?: MagicLinkVerifyParams): string {
    return buildRoute("/[locale]/magic-link/verify", params);
}

function Register(params?: RegisterParams): string {
    return buildRoute("/[locale]/register", params);
}

function ResetPassword(params?: ResetPasswordParams): string {
    return buildRoute("/[locale]/reset-password", params);
}

function VerifyEmail(params?: VerifyEmailParams): string {
    return buildRoute("/[locale]/verify-email", params);
}

function Home(params?: HomeParams): string {
    return buildRoute("/[locale]", params);
}

function HostingCalendar(params?: HostingCalendarParams): string {
    return buildRoute("/[locale]/hosting/calendar", params);
}

function MyActivities(params?: MyActivitiesParams): string {
    return buildRoute("/[locale]/hosting/host", params);
}

function Temp(params?: TempParams): string {
    return buildRoute("/[locale]/hosting/host/temp", params);
}

function ActivityBookings(params: ActivityBookingsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/bookings", params);
}

function ActivityInvoices(params: ActivityInvoicesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/invoices", params);
}

function ActivityOverview(params: ActivityOverviewParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/overview", params);
}

function ActivityDetails(params: ActivityDetailsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]", params);
}

function ActivityAvailabilities(params: ActivityAvailabilitiesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/availabilities", params);
}

function ActivityCollaborators(params: ActivityCollaboratorsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/collaborators", params);
}

function ActivityCycles(params: ActivityCyclesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/cycles", params);
}

function ActivitySettingsInformations(params: ActivitySettingsInformationsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/informations", params);
}

function ActivitySettings(params: ActivitySettingsParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings", params);
}

function ActivityPayment(params: ActivityPaymentParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/payment", params);
}

function ActivityServices(params: ActivityServicesParams): string {
    return buildRoute("/[locale]/hosting/host/[id]/settings/services", params);
}

function HostingMessages(params?: HostingMessagesParams): string {
    return buildRoute("/[locale]/hosting/messages", params);
}

function HostingNow(params?: HostingNowParams): string {
    return buildRoute("/[locale]/hosting/now", params);
}

function HostingScan(params?: HostingScanParams): string {
    return buildRoute("/[locale]/hosting/scan", params);
}

function HostingScanResult(params: HostingScanResultParams): string {
    return buildRoute("/[locale]/hosting/scan/[microchip]", params);
}

function HostingSubscription(params?: HostingSubscriptionParams): string {
    return buildRoute("/[locale]/hosting/subscription", params);
}

function Cgu(params?: CguParams): string {
    return buildRoute("/[locale]/cgu", params);
}

function Cgv(params?: CgvParams): string {
    return buildRoute("/[locale]/cgv", params);
}

function Confidentialite(params?: ConfidentialiteParams): string {
    return buildRoute("/[locale]/confidentialite", params);
}

function Contact(params?: ContactParams): string {
    return buildRoute("/[locale]/contact", params);
}

function BecomeHost(params?: BecomeHostParams): string {
    return buildRoute("/[locale]/become-host", params);
}

function BookingDetail(params: BookingDetailParams): string {
    return buildRoute("/[locale]/bookings/[id]", params);
}

function Explore(params?: ExploreParams): string {
    return buildRoute("/[locale]/explore", params);
}

function ExploreResults(params?: ExploreResultsParams): string {
    return buildRoute("/[locale]/explore/results", params);
}

function Favorites(params?: FavoritesParams): string {
    return buildRoute("/[locale]/favorites", params);
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

function Notifications(params?: NotificationsParams): string {
    return buildRoute("/[locale]/notifications", params);
}

function LivePet(params?: LivePetParams): string {
    return buildRoute("/[locale]/pets/live", params);
}

function NewPet(params?: NewPetParams): string {
    return buildRoute("/[locale]/pets/new", params);
}

function MyPets(params?: MyPetsParams): string {
    return buildRoute("/[locale]/pets", params);
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

function PaymentMethodsPage(params?: PaymentMethodsPageParams): string {
    return buildRoute("/[locale]/settings/payment-methods", params);
}

function MyProfileEmailPreferences(params?: MyProfileEmailPreferencesParams): string {
    return buildRoute("/[locale]/settings/preferences-email", params);
}

function MyProfilePreferencesNotification(params?: MyProfilePreferencesNotificationParams): string {
    return buildRoute("/[locale]/settings/preferences-notification", params);
}

function SettingsScanners(params?: SettingsScannersParams): string {
    return buildRoute("/[locale]/settings/scanners", params);
}

function MyProfileTwoFactor(params?: MyProfileTwoFactorParams): string {
    return buildRoute("/[locale]/settings/two-factor", params);
}

export const routes = {
    RootPage,
    ForgotPassword,
    Login,
    MagicLink,
    MagicLinkVerify,
    Register,
    ResetPassword,
    VerifyEmail,
    Home,
    HostingCalendar,
    MyActivities,
    Temp,
    ActivityBookings,
    ActivityInvoices,
    ActivityOverview,
    ActivityDetails,
    ActivityAvailabilities,
    ActivityCollaborators,
    ActivityCycles,
    ActivitySettingsInformations,
    ActivitySettings,
    ActivityPayment,
    ActivityServices,
    HostingMessages,
    HostingNow,
    HostingScan,
    HostingScanResult,
    HostingSubscription,
    Cgu,
    Cgv,
    Confidentialite,
    Contact,
    BecomeHost,
    BookingDetail,
    Explore,
    ExploreResults,
    Favorites,
    HostBook,
    HostDetail,
    Messages,
    Notifications,
    LivePet,
    NewPet,
    MyPets,
    PetEditGeneral,
    PetEditHealth,
    PetEditPage,
    PetEditPersonality,
    PetEditPhotos,
    PetDetails,
    Profile,
    MyProfileAbout,
    MyProfileChangePassword,
    Settings,
    PaymentMethodsPage,
    MyProfileEmailPreferences,
    MyProfilePreferencesNotification,
    SettingsScanners,
    MyProfileTwoFactor,
} as const;

export type RouteName = keyof typeof routes;
