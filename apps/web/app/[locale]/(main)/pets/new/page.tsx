import { getTranslations } from "next-intl/server";
import NewPetPage from "./new-pet-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: t("features.pets.create.pageTitle"),
        description: t("features.pets.create.pageDescription"),
    };
}

export default function NewPet() {
    return <NewPetPage />;
}
