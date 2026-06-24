import type { BookingDto } from "../../../bookings/models/dtos/booking.dto";
import type { PetDto } from "../../../pets/models/dtos/pet.dto";
import type { ScanOwnerDto } from "./scan-owner.dto";

export type HostScanLookupDto = {
    found: boolean;
    pet: PetDto | null;
    owner: ScanOwnerDto | null;
    current_booking: BookingDto | null;
    past_bookings: BookingDto[];
};
