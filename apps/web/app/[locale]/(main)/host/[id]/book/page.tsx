import { getTranslations } from "next-intl/server";
import BookingPage from "./booking-page";

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
        title: t("features.bookings.checkout.title"),
    };
}

export default function HostBook() {
    return <BookingPage />;
}
