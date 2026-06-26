import type { BookingQuoteDto } from "./dtos/booking-quote.dto";

export class BookingQuoteModel {
    private constructor(
        public readonly nights: number,
        public readonly basePrice: number,
        public readonly serviceFee: number,
        public readonly platformFee: number,
        public readonly activityAmount: number,
        public readonly totalPrice: number,
    ) {}

    static from(dto: BookingQuoteDto): BookingQuoteModel {
        const totalPrice = Number(dto.total_price);
        const serviceFee = Number(dto.service_fee);
        const basePrice = Number((totalPrice - serviceFee).toFixed(2));

        return new BookingQuoteModel(
            dto.nights,
            basePrice,
            serviceFee,
            Number(dto.platform_fee),
            Number(dto.activity_amount),
            totalPrice,
        );
    }

    get serviceFeePercent(): number {
        if (this.basePrice <= 0) {
            return 0;
        }
        return Math.round((this.serviceFee / this.basePrice) * 100);
    }
}
