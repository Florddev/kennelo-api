"use client";

import { useSearchParams } from "next/navigation";

import { useNavigation } from "@/hooks/use-navigation";
import {
    HostDetailContent,
    HostDetailSkeleton,
    HostNotFound,
    parseDateRangeFromParams,
    useHostEstablishment,
    useHostAvailabilities,
} from "@/features/host";

export default function HostDetailPage() {
    const { router, params } = useNavigation<{ id: string }>();
    const id = params.id ?? "";
    const searchParams = useSearchParams();

    const { establishment, capacities, isLoading } = useHostEstablishment(id);
    const { availabilities } = useHostAvailabilities(id);

    if (isLoading) {
        return <HostDetailSkeleton />;
    }

    if (!establishment) {
        return <HostNotFound onBack={() => router.back()} />;
    }

    const initialDateRange = parseDateRangeFromParams(
        searchParams.get("check_in"),
        searchParams.get("check_out"),
    );

    return (
        <HostDetailContent
            establishment={establishment}
            capacities={capacities}
            availabilities={availabilities}
            initialDateRange={initialDateRange}
            onBack={() => router.back()}
        />
    );
}
