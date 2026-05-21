import { api } from "@workspace/common";
import { EstablishmentModel } from "../../models/establishment.model";
import { ExploreSectionPageDto } from "../../models/dtos/explore-sections-response.dto";
import type { ExploreCoords } from "./get-explore-establishments";

export type SearchEstablishmentsInput = {
    coords?: ExploreCoords;
    animalTypes?: string[];
    hostType?: "pro" | "individual";
    minRating?: number;
    maxPrice?: number;
    sort?: "rating" | "distance" | "price";
    page?: number;
};

export type SearchEstablishmentsResult = {
    establishments: EstablishmentModel[];
    meta: {
        currentPage: number;
        perPage: number;
        hasMore: boolean;
    };
};

export async function searchEstablishments(
    input: SearchEstablishmentsInput,
): Promise<SearchEstablishmentsResult> {
    const params: Record<string, unknown> = {
        page: input.page ?? 1,
    };

    if (input.coords) {
        params.lat = input.coords.lat;
        params.lng = input.coords.lng;
    }
    if (input.animalTypes?.length) {
        params.animal_types = input.animalTypes.join(",");
    }
    if (input.hostType) {
        params.host_type = input.hostType;
    }
    if (input.minRating !== undefined) {
        params.min_rating = input.minRating;
    }
    if (input.maxPrice !== undefined) {
        params.max_price = input.maxPrice;
    }
    if (input.sort) {
        params.sort = input.sort;
    }

    const response = await api.get<ExploreSectionPageDto>("/explore/search", params);

    if (!response.data) {
        return {
            establishments: [],
            meta: { currentPage: 1, perPage: 10, hasMore: false },
        };
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
