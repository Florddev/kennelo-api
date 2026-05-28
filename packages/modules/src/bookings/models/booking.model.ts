import { EstablishmentModel } from "../../establishments/models/establishment.model";
import { UserModel } from "../../users/models/user.model";
import { BookingPetModel } from "./booking-pet.model";
import { BookingServiceModel } from "./booking-service.model";
import type { BookingStatus } from "../types/booking-status.type";
import type { BookingDto } from "./dtos/booking.dto";

export class BookingModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly establishmentId: string,
        public readonly checkInDate: string,
        public readonly checkOutDate: string,
        public readonly totalPrice: string,
        public readonly platformFee: string,
        public readonly establishmentAmount: string,
        public readonly status: BookingStatus,
        public readonly paymentStatus: string | null,
        public readonly stripePaymentIntentId: string | null,
        public readonly clientSecret: string | null,
        public readonly checkoutUrl: string | null,
        public readonly specialRequests: string | null,
        public readonly paidAt: string | null,
        public readonly user: UserModel | null,
        public readonly establishment: EstablishmentModel | null,
        public readonly pets: BookingPetModel[] | null,
        public readonly services: BookingServiceModel[] | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: BookingDto): BookingModel {
        return new BookingModel(
            dto.id,
            dto.user_id,
            dto.establishment_id,
            dto.check_in_date,
            dto.check_out_date,
            dto.total_price,
            dto.platform_fee,
            dto.establishment_amount,
            dto.status,
            dto.payment_status,
            dto.stripe_payment_intent_id,
            dto.client_secret ?? null,
            dto.checkout_url ?? null,
            dto.special_requests,
            dto.paid_at,
            dto.user ? UserModel.from(dto.user) : null,
            dto.establishment ? EstablishmentModel.from(dto.establishment) : null,
            dto.pets ? dto.pets.map(BookingPetModel.from) : null,
            dto.services ? dto.services.map(BookingServiceModel.from) : null,
            dto.created_at,
            dto.updated_at,
        );
    }

    private isStatus(status: BookingStatus): boolean {
        return this.status === status;
    }

    isPending(): boolean {
        return this.isStatus("pending");
    }

    isConfirmed(): boolean {
        return this.isStatus("confirmed");
    }

    isCompleted(): boolean {
        return this.isStatus("completed");
    }

    isCancelled(): boolean {
        return this.isStatus("cancelled");
    }
}
