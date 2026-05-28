import type { SearchParams } from "next/dist/server/request/search-params";
import { getTranslations } from "next-intl/server";
import ExploreResultsPage from "./results-page";

type Props = {
    params: Promise<{ locale: string }>;
    searchParams: Promise<SearchParams>;
};

const PET_TYPES = ["dog", "cat", "bird", "reptile"];

export async function generateMetadata({ params }: Props) {
    const { locale } = await params;
    const t = await getTranslations({ locale });
    return {
        title: t("features.explore.title"),
        description: t("features.explore.description"),
    };
}

export default async function ExploreResults({ searchParams }: Omit<Props, "params">) {
    const params = await searchParams;

    const location = typeof params.location === "string" ? params.location : "";
    const dateFrom = typeof params.dateFrom === "string" ? params.dateFrom : "";
    const dateTo = typeof params.dateTo === "string" ? params.dateTo : "";

    const petCounts: Record<string, number> = {};
    PET_TYPES.forEach((type) => {
        const val = params[type];
        if (typeof val === "string") {
            const count = parseInt(val, 10);
            if (count > 0) petCounts[type] = count;
        }
    });

    return (
        <ExploreResultsPage
            location={location}
            dateFrom={dateFrom}
            dateTo={dateTo}
            petCounts={petCounts}
        />
    );
}
