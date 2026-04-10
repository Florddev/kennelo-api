import type { BookingServiceDto } from "./dtos/booking-service.dto";

export class BookingServiceModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly quantity: number,
        public readonly unitPrice: string,
        public readonly subtotal: string,
    ) {}

    static from(dto: BookingServiceDto): BookingServiceModel {
        return new BookingServiceModel(
            dto.id,
            dto.name,
            dto.quantity,
            dto.unit_price,
            dto.subtotal,
        );
    }
}
