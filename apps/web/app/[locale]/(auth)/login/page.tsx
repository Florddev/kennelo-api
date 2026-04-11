import { getTranslations } from "next-intl/server";
import LoginPage from "./login-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.login.title"),
        description: t("features.auth.login.description"),
    };
}

export default function Login() {
    return <LoginPage />;
}
