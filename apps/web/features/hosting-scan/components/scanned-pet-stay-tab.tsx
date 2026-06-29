"use client";

import type { BookingModel } from "@workspace/modules/bookings";

import { PastBookingsList } from "./past-bookings-list";
import { ScannedPetCurrentBooking } from "./scanned-pet-current-booking";

export function ScannedPetStayTab({
    currentBooking,
    pastBookings,
}: {
    currentBooking: BookingModel | null;
    pastBookings: BookingModel[];
}) {
    return (
        <div className="flex flex-col gap-6">
            <ScannedPetCurrentBooking booking={currentBooking} />
            <PastBookingsList bookings={pastBookings} />
        </div>
    );
}
