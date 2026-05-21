import { EstablishmentDto } from "./establishment.dto";

export type ExploreSectionDto = {
    id: string;
    has_more: boolean;
    establishments: EstablishmentDto[];
};

export type ExploreSectionsResponseDto = {
    sections: ExploreSectionDto[];
};

export type ExploreSectionPageDto = {
    establishments: EstablishmentDto[];
    meta: {
        current_page: number;
        per_page: number;
        has_more: boolean;
    };
};
