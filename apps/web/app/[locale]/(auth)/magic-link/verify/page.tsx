import { getTranslations } from "next-intl/server";
import MagicLinkVerifyPage from "./magic-link-verify-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.magicLink.title"),
    };
}

export default function MagicLinkVerify() {
    return <MagicLinkVerifyPage />;
}
