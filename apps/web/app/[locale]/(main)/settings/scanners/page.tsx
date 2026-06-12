import type { Metadata } from "next";
import { getTranslations } from "next-intl/server";

import { ScannersSettingsPage } from "./scanners-settings-page";

type Props = { params: Promise<{ locale: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.scanners.title"),
        description: t("features.scanners.description"),
    };
}

export default function SettingsScanners() {
    return <ScannersSettingsPage />;
}
