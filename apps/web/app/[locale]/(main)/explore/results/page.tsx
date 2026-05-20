import type { SearchParams } from "next/dist/server/request/search-params";
import ExploreResultsPage from "./results-page";

type Props = {
    searchParams: Promise<SearchParams>;
};

export default async function ExploreResults({ searchParams }: Props) {
    const params = await searchParams;

    const location = typeof params.location === "string" ? params.location : "";
    const dateFrom = typeof params.dateFrom === "string" ? params.dateFrom : "";
    const dateTo = typeof params.dateTo === "string" ? params.dateTo : "";
    const pets = typeof params.pets === "string" ? params.pets : "";

    return (
        <ExploreResultsPage location={location} dateFrom={dateFrom} dateTo={dateTo} pets={pets} />
    );
}
