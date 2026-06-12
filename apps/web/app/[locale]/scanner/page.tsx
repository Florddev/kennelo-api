import { getTranslations } from "next-intl/server";

import ScannerPage from "./scanner-page";

export async function generateMetadata() {
    const t = await getTranslations();
    return {
        title: t("features.scanners.title"),
        description: t("features.scanners.description"),
    };
}

export default function ScannerConfigPage() {
    return <ScannerPage />;
}
