import { getTranslations } from "next-intl/server";
import TempPage from "./temp-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.establishments.detail.sections.payments"),
    };
}

export default function Temp() {
    return <TempPage />;
}
