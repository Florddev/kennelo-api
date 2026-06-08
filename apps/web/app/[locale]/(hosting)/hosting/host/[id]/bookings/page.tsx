import { getTranslations } from "next-intl/server";
import ActivityBookingsPage from "./activity-bookings-page";

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
        title: t("features.activities.manager.bookings.title"),
    };
}

export default function ActivityBookings() {
    return <ActivityBookingsPage />;
}
