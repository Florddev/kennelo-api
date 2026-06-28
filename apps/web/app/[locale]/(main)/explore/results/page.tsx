import type { SearchParams } from "next/dist/server/request/search-params";
import { getTranslations } from "next-intl/server";
import ExploreResultsPage from "./results-page";

export async function generateMetadata({
    params,
}: {
    params: Promise<{ locale: string }>;
    searchParams: Promise<SearchParams>;
}) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.explore.title"),
        description: t("features.explore.description"),
    };
}

export default async function ExploreResults() {
    return <ExploreResultsPage />;
}
