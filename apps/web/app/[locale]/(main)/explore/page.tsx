import { getTranslations } from "next-intl/server";
import ExplorePage from "./explore-page";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.explore.title"),
        description: t("features.explore.description"),
    };
}

export default function Explore() {
    return <ExplorePage />;
}
