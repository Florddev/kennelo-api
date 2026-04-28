"use client";

import { useMemo } from "react";
import { useSearchParams } from "next/navigation";
import { fromApiDate } from "@workspace/common";

import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth";
import { usePets } from "@/features/pets/hooks/use-pets";
import { useHostEstablishment } from "@/features/host";
import { BookingCheckoutForm, BookingSkeleton } from "@/features/bookings";

export default function BookingPage() {
    const { router, routes, params } = useNavigation<{ id: string }>();
    const searchParams = useSearchParams();
    const id = params.id ?? "";
    const checkIn = searchParams.get("check_in");
    const checkOut = searchParams.get("check_out");
    const { isAuthenticated, isLoaded } = useAuth();

    const { establishment, capacities, isLoading } = useHostEstablishment(id);
    const { pets, isLoading: isLoadingPets } = usePets();

    const dateRange = useMemo(() => {
        if (!checkIn || !checkOut) return null;
        try {
            return { from: fromApiDate(checkIn), to: fromApiDate(checkOut) };
        } catch {
            return null;
        }
    }, [checkIn, checkOut]);

    if (isLoaded && !isAuthenticated) {
        router.replace(routes.Login());
        return null;
    }

    if (isLoading || !establishment || !dateRange) {
        return <BookingSkeleton />;
    }

    return (
        <BookingCheckoutForm
            establishment={establishment}
            capacities={capacities}
            pets={pets}
            isLoadingPets={isLoadingPets}
            dateRange={dateRange}
        />
    );
}
