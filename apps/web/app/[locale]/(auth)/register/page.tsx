import { getTranslations } from "next-intl/server";
import RegisterPage from "./register-page";

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.auth.register.title"),
        description: t("features.auth.register.description"),
    };
}

export default function Register() {
    return <RegisterPage />;
}
