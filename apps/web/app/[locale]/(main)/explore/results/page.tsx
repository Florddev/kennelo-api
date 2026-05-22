import type { SearchParams } from "next/dist/server/request/search-params";
import ExploreResultsPage from "./results-page";

type Props = {
    searchParams: Promise<SearchParams>;
};

const PET_TYPES = ["dog", "cat", "bird", "reptile"];

export default async function ExploreResults({ searchParams }: Props) {
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
