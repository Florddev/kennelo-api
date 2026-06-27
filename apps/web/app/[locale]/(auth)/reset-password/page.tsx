import { getTranslations } from "next-intl/server";
import ResetPasswordPage from "./reset-password-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.resetPassword.title"),
        description: t("features.auth.resetPassword.description"),
    };
}

export default function ResetPassword() {
    return <ResetPasswordPage />;
}
