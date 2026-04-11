import { getTranslations } from "next-intl/server";
import MyPetsPage from "./my-pets-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.pets.title"),
        description: t("features.pets.description"),
    };
}

export default function MyPets() {
    return <MyPetsPage />;
}
