import { getTranslations } from "next-intl/server";
import { HostingScanPage } from "./scan-page";
import HostingLayout from "../hosting-layout";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.hosting-scan.title"),
        description: t("features.hosting-scan.description"),
    };
}

export default function HostingScan() {
    return (
        <HostingLayout>
            <HostingScanPage />
        </HostingLayout>
    );
}
