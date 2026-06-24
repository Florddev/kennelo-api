import { getTranslations } from "next-intl/server";
import NotificationsPage from "./notifications-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.notifications.title"),
    };
}

export default function Notifications() {
    return (
        <div className="container mx-auto">
            <NotificationsPage />
        </div>
    );
}
