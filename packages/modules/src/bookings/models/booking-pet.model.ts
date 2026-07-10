import { AnimalTypeModel } from "../../pets/models/animal-type.model";
import type { BookingPetDto } from "./dtos/booking-pet.dto";

export class BookingPetModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly pricePerNight: string,
        public readonly numberOfNights: number,
        public readonly subtotal: string,
        public readonly animalType: AnimalTypeModel | null,
    ) {}

    static from(dto: BookingPetDto): BookingPetModel {
        return new BookingPetModel(
            dto.id,
            dto.name,
            dto.price_per_night,
            dto.number_of_nights,
            dto.subtotal,
            dto.animal_type ? AnimalTypeModel.from(dto.animal_type) : null,
        );
    }
}
