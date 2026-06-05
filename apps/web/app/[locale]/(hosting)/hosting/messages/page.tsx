import { getTranslations } from "next-intl/server";
import HostingMessagesPage from "./hosting-messages-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.hosting-messages.title"),
        description: t("features.hosting-messages.description"),
    };
}

export default function HostingMessages() {
    return <HostingMessagesPage />;
}
