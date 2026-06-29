import { BookingModel } from "../../bookings/models/booking.model";
import { PetModel } from "../../pets/models/pet.model";
import type { HostScanLookupDto } from "./dtos/host-scan-lookup.dto";
import { ScanOwnerModel } from "./scan-owner.model";

export class HostScanLookupModel {
    private constructor(
        public readonly found: boolean,
        public readonly pet: PetModel | null,
        public readonly owner: ScanOwnerModel | null,
        public readonly currentBooking: BookingModel | null,
        public readonly pastBookings: BookingModel[],
    ) {}

    static from(dto: HostScanLookupDto): HostScanLookupModel {
        return new HostScanLookupModel(
            dto.found,
            dto.pet ? PetModel.from(dto.pet) : null,
            dto.owner ? ScanOwnerModel.from(dto.owner) : null,
            dto.current_booking ? BookingModel.from(dto.current_booking) : null,
            dto.past_bookings ? dto.past_bookings.map(BookingModel.from) : [],
        );
    }
}
