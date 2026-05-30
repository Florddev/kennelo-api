import { api } from "@workspace/common";
import { EstablishmentModel } from "../../models/establishment.model";
import { ExploreSectionPageDto } from "../../models/dtos/explore-sections-response.dto";
import type { ExploreCoords } from "./get-explore-establishments";

export type ExploreSectionPage = {
    establishments: EstablishmentModel[];
    meta: {
        currentPage: number;
        perPage: number;
        hasMore: boolean;
    };
};

export async function getExploreSection(
    sectionId: string,
    page: number,
    coords?: ExploreCoords,
): Promise<ExploreSectionPage> {
    const params: Record<string, string | number | boolean> = { page };
    if (coords) {
        params.lat = coords.lat;
        params.lng = coords.lng;
    }

    const response = await api.get<ExploreSectionPageDto>(
        `/explore/establishments/sections/${sectionId}`,
        params,
    );

    if (!response.data) {
        return { establishments: [], meta: { currentPage: page, perPage: 10, hasMore: false } };
    }

    return {
        establishments: response.data.establishments.map(EstablishmentModel.from),
        meta: {
            currentPage: response.data.meta.current_page,
            perPage: response.data.meta.per_page,
            hasMore: response.data.meta.has_more,
        },
    };
}
