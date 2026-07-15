import { getTranslations } from "next-intl/server";
import MagicLinkPage from "./magic-link-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.magicLink.title"),
        description: t("features.auth.magicLink.description"),
    };
}

export default function MagicLink() {
    return <MagicLinkPage />;
}
