import { api } from "@workspace/common";
import { ActivityModel } from "../../models/activity.model";
import { ActivityDto } from "../../models/dtos/activity.dto";

const PER_PAGE = 15;

export type FavoritesPage = {
    activities: ActivityModel[];
    meta: { currentPage: number; perPage: number; hasMore: boolean };
};

export async function getFavorites(page = 1): Promise<FavoritesPage> {
    const response = await api.get<ActivityDto[]>("/favorites", { page, per_page: PER_PAGE });
    const activities = (response.data ?? []).map(ActivityModel.from);

    return {
        activities,
        meta: { currentPage: page, perPage: PER_PAGE, hasMore: activities.length === PER_PAGE },
    };
}
