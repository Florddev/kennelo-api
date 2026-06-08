"use client";

import { useSearchParams } from "next/navigation";

import { useNavigation } from "@/hooks/use-navigation";
import {
    HostDetailContent,
    HostDetailSkeleton,
    HostNotFound,
    parseDateRangeFromParams,
    useHostActivity,
    useHostAvailabilities,
} from "@/features/host";

export default function HostDetailPage() {
    const { router, params } = useNavigation<{ id: string }>();
    const id = params.id ?? "";
    const searchParams = useSearchParams();

    const { activity, capacities, isLoading } = useHostActivity(id);
    const { availabilities } = useHostAvailabilities(id);

    if (isLoading) {
        return <HostDetailSkeleton />;
    }

    if (!activity) {
        return <HostNotFound onBack={() => router.back()} />;
    }

    const initialDateRange = parseDateRangeFromParams(
        searchParams.get("check_in"),
        searchParams.get("check_out"),
    );

    return (
        <HostDetailContent
            activity={activity}
            capacities={capacities}
            availabilities={availabilities}
            initialDateRange={initialDateRange}
            onBack={() => router.back()}
        />
    );
}
