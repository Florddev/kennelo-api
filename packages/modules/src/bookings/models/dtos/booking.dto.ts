import type { EstablishmentDto } from "../../../establishments/models/dtos/establishment.dto";
import type { BookingStatus } from "../../types/booking-status.type";
import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { BookingPetDto } from "./booking-pet.dto";
import type { BookingServiceDto } from "./booking-service.dto";

export type BookingDto = {
    id: string;
    user_id: string;
    establishment_id: string;
    check_in_date: string;
    check_out_date: string;
    total_price: string;
    platform_fee: string;
    establishment_amount: string;
    status: BookingStatus;
    payment_status: string | null;
    stripe_payment_intent_id: string | null;
    client_secret?: string | null;
    checkout_url?: string | null;
    special_requests: string | null;
    paid_at: string | null;
    user?: UserDto | null;
    establishment?: EstablishmentDto | null;
    pets?: BookingPetDto[];
    services?: BookingServiceDto[];
    created_at: string;
    updated_at: string;
};
