import { getTranslations } from "next-intl/server";
import BookingDetailPage from "./booking-detail-page";

export type Query = {
    id: string;
};

export function generateStaticParams(): Query[] {
    return [{ id: "[id]" }];
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.bookings.detail.title"),
        description: t("features.bookings.detail.description"),
    };
}

export default function BookingDetail() {
    return <BookingDetailPage />;
}
