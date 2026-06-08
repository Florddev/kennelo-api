import { ActivityDto } from "./activity.dto";

export type ExploreSectionDto = {
    id: string;
    has_more: boolean;
    activities: ActivityDto[];
};

export type ExploreSectionsResponseDto = {
    sections: ExploreSectionDto[];
};

export type ExploreSectionPageDto = {
    activities: ActivityDto[];
    meta: {
        current_page: number;
        per_page: number;
        has_more: boolean;
    };
};
