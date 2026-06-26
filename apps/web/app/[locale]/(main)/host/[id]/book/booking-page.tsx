"use client";

import { useMemo } from "react";
import { useSearchParams } from "next/navigation";
import { fromApiDate } from "@workspace/common";

import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth";
import { usePets } from "@/features/pets/hooks/use-pets";
import {
    useHostActivity,
    useHostAvailabilities,
    useHostPriceCalendar,
    useHostPublicCycles,
} from "@/features/host";
import { BookingCheckoutForm, BookingSkeleton } from "@/features/bookings";

export default function BookingPage() {
    const { router, routes, params } = useNavigation<{ id: string }>();
    const searchParams = useSearchParams();
    const id = params.id ?? "";
    const checkIn = searchParams.get("check_in");
    const checkOut = searchParams.get("check_out");
    const petIdsParam = searchParams.get("pet_ids");
    const { isAuthenticated, isLoaded } = useAuth();

    const { activity, capacities, isLoading } = useHostActivity(id);
    const { availabilities } = useHostAvailabilities(id);
    const { priceMap } = useHostPriceCalendar(id);
    const { cycles } = useHostPublicCycles(id);
    const { pets } = usePets();

    const initialDateRange = useMemo(() => {
        if (!checkIn || !checkOut) return null;
        try {
            return { from: fromApiDate(checkIn), to: fromApiDate(checkOut) };
        } catch {
            return null;
        }
    }, [checkIn, checkOut]);

    const initialPetIds = useMemo(
        () => (petIdsParam ? petIdsParam.split(",").filter(Boolean) : []),
        [petIdsParam],
    );

    if (isLoaded && !isAuthenticated) {
        router.replace(routes.Login());
        return null;
    }

    if (isLoading || !activity) {
        return <BookingSkeleton />;
    }

    return (
        <BookingCheckoutForm
            activity={activity}
            capacities={capacities}
            pets={pets}
            cycles={cycles}
            initialDateRange={initialDateRange}
            initialPetIds={initialPetIds}
            availabilities={availabilities}
            priceMap={priceMap}
        />
    );
}
