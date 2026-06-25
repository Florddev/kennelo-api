import { AnimalTypeModel } from "./animal-type.model";
import type { AnimalTypePriceRangeDto } from "./dtos/animal-type-price-range.dto";

export class AnimalTypePriceRangeModel {
    private constructor(
        public readonly animalType: AnimalTypeModel,
        public readonly minPrice: number,
        public readonly maxPrice: number,
    ) {}

    static from(dto: AnimalTypePriceRangeDto): AnimalTypePriceRangeModel {
        return new AnimalTypePriceRangeModel(
            AnimalTypeModel.from(dto.animal_type),
            Number(dto.min_price),
            Number(dto.max_price),
        );
    }
}
