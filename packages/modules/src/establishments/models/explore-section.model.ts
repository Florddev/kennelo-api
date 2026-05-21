import { EstablishmentModel } from "./establishment.model";
import { ExploreSectionDto } from "./dtos/explore-sections-response.dto";

export class ExploreSectionModel {
    private constructor(
        public readonly id: string,
        public readonly hasMore: boolean,
        public readonly establishments: EstablishmentModel[],
    ) {}

    static from(dto: ExploreSectionDto): ExploreSectionModel {
        return new ExploreSectionModel(
            dto.id,
            dto.has_more,
            dto.establishments.map(EstablishmentModel.from),
        );
    }
}
