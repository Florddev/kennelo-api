import { getTranslations } from "next-intl/server";

import LivePetPage from "./live-pet-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.pets.live.title"),
        description: t("features.pets.live.description"),
    };
}

export default function LivePet() {
    return (
        <div className="container mx-auto">
            <LivePetPage />
        </div>
    );
}
