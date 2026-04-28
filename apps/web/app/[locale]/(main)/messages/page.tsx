import { getTranslations } from "next-intl/server";
import MessagesPage from "./messages-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.conversations.title"),
        description: t("features.conversations.description"),
    };
}

export default function Messages() {
    return <MessagesPage />;
}
