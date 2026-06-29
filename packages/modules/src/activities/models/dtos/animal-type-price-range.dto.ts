import type { AnimalTypeDto } from "./animal-type.dto";

export type AnimalTypePriceRangeDto = {
    animal_type: AnimalTypeDto;
    min_price: string | number;
    max_price: string | number;
};
