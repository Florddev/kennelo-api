import { getTranslations } from "next-intl/server";
import ProfilePage from "./profile-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.profile.title"),
        description: t("features.profile.description"),
    };
}

export default function Profile() {
    return <ProfilePage />;
}
