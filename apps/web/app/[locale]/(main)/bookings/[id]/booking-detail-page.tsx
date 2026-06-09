"use client";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { useNavigation } from "@/hooks/use-navigation";
import { useBooking } from "@/features/bookings/hooks/use-booking";
import { usePets } from "@/features/pets/hooks/use-pets";
import { BookingDetailContent } from "@/features/bookings/components/booking-detail-content";

export default function BookingDetailPage() {
    const { router, params } = useNavigation<{ id: string }>();
    const bookingId = params.id ?? "";

    const { booking, isLoading } = useBooking(bookingId);
    const { pets } = usePets();

    if (isLoading) {
        return (
            <div className="flex flex-col gap-4 p-4">
                <Skeleton className="aspect-[4/3] w-full rounded-none" />
                <div className="flex flex-col gap-3 px-4">
                    <Skeleton className="h-6 w-48" />
                    <Skeleton className="h-32 w-full rounded-2xl" />
                    <Skeleton className="h-6 w-48" />
                    <Skeleton className="h-40 w-full rounded-2xl" />
                    <Skeleton className="h-6 w-48" />
                    <Skeleton className="h-32 w-full rounded-2xl" />
                </div>
            </div>
        );
    }

    if (!booking) {
        return null;
    }

    return <BookingDetailContent booking={booking} userPets={pets} onBack={() => router.back()} />;
}
