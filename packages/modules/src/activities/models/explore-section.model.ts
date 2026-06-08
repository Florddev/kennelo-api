import { ActivityModel } from "./activity.model";
import { ExploreSectionDto } from "./dtos/explore-sections-response.dto";

export class ExploreSectionModel {
    private constructor(
        public readonly id: string,
        public readonly hasMore: boolean,
        public readonly activities: ActivityModel[],
    ) {}

    static from(dto: ExploreSectionDto): ExploreSectionModel {
        return new ExploreSectionModel(
            dto.id,
            dto.has_more,
            dto.activities.map(ActivityModel.from),
        );
    }
}
