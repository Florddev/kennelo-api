import { api } from "@workspace/common";
import { ExploreSectionModel } from "../../models/explore-section.model";
import { ExploreSectionsResponseDto } from "../../models/dtos/explore-sections-response.dto";

export type ExploreCoords = {
    lat: number;
    lng: number;
};

export async function getExploreEstablishments(
    coords?: ExploreCoords,
): Promise<ExploreSectionModel[]> {
    const params = coords ? { lat: coords.lat, lng: coords.lng } : undefined;
    const response = await api.get<ExploreSectionsResponseDto>("/explore/establishments", params);

    if (!response.data) {
        return [];
    }

    return response.data.sections.map(ExploreSectionModel.from);
}
