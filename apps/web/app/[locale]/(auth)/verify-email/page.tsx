import { getTranslations } from "next-intl/server";
import VerifyEmailPage from "./verify-email-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.verifyEmail.title"),
    };
}

export default function VerifyEmail() {
    return <VerifyEmailPage />;
}
