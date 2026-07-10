import type { AnimalTypeDto } from "../../../pets/models/dtos/animal-type.dto";

export type BookingPetDto = {
    id: string;
    name: string;
    price_per_night: string;
    number_of_nights: number;
    subtotal: string;
    animal_type?: AnimalTypeDto | null;
};
