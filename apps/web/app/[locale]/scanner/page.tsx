import { getTranslations } from "next-intl/server";

import ScannerPage from "./scanner-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale, namespace: "features.scanners" });
    return {
        title: t("title"),
        description: t("description"),
    };
}

export default function Page() {
    return <ScannerPage />;
}
