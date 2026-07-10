import { getTranslations } from "next-intl/server";
import HostingCalendarPage from "./hosting-calendar-page";
import HostingLayout from "../hosting-layout";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.hosting-calendar.title"),
        description: t("features.hosting-calendar.description"),
    };
}

export default function HostingCalendar() {
    return (
        <HostingLayout>
            <HostingCalendarPage />
        </HostingLayout>
    );
}
