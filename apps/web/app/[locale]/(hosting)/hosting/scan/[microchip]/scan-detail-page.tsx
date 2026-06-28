"use client";

import { PetDetailSkeleton } from "@/features/pets/components/pet-detail-skeleton";
import { useRouteParams } from "@/hooks/use-route-params";
import { ScannedPetDetail, ScannedPetUnknown, useHostScanLookup } from "@/features/hosting-scan";

export function HostingScanResultPage() {
    const { microchip } = useRouteParams<{ microchip: string }>();
    const microchipNumber = microchip ?? "";

    const { lookup, isLoading, refetch } = useHostScanLookup(microchipNumber);

    if (isLoading || !lookup) {
        return <PetDetailSkeleton />;
    }

    if (!lookup.found || !lookup.pet) {
        return <ScannedPetUnknown microchip={microchipNumber} onAssigned={() => refetch()} />;
    }

    return (
        <ScannedPetDetail
            pet={lookup.pet}
            owner={lookup.owner}
            currentBooking={lookup.currentBooking}
            pastBookings={lookup.pastBookings}
        />
    );
}
