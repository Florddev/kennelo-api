import { getTranslations } from "next-intl/server";
import EstablishmentBookingsPage from "./establishment-bookings-page";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.my-establishments.manager.bookings.title"),
    };
}

export default function EstablishmentBookings() {
    return <EstablishmentBookingsPage />;
}
