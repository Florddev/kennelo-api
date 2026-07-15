import { getTranslations } from "next-intl/server";
import { HostingScanResultPage } from "./scan-detail-page";

export type Query = {
    microchip: string;
};

export function generateStaticParams(): Query[] {
    return [{ microchip: "[microchip]" }];
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.hosting-scan.title"),
        description: t("features.hosting-scan.description"),
    };
}

export default function HostingScanResult() {
    return <HostingScanResultPage />;
}
