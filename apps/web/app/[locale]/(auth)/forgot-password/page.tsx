import { getTranslations } from "next-intl/server";
import ForgotPasswordPage from "./forgot-password-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.forgotPassword.title"),
        description: t("features.auth.forgotPassword.description"),
    };
}

export default function ForgotPassword() {
    return <ForgotPasswordPage />;
}
