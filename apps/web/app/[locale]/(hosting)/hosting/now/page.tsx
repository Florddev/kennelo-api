import { getTranslations } from "next-intl/server";
import HostingNowPage from "./hosting-now-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.hosting-today.title"),
        description: t("features.hosting-today.description"),
    };
}

export default function HostingNow() {
    return <HostingNowPage />;
}
