import { getTranslations } from "next-intl/server";
import FavoritesPage from "./favorites-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.favorites.title"),
        description: t("features.favorites.description"),
    };
}

export default function Favorites() {
    return (
        <div className="container mx-auto">
            <FavoritesPage />
        </div>
    );
}
