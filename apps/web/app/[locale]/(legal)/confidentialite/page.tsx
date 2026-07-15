import { getTranslations } from "next-intl/server";
import ConfidentialitePage from "./confidentialite-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.legal.confidentialite.title"),
    };
}

export default function Confidentialite() {
    return <ConfidentialitePage />;
}
