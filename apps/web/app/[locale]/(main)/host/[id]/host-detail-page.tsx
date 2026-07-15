"use client";

import { useEffect, useRef } from "react";
import { useSearchParams } from "next/navigation";
import posthog from "posthog-js";

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

    const trackedRef = useRef(false);
    useEffect(() => {
        if (activity && !trackedRef.current) {
            trackedRef.current = true;
            posthog.capture("host_viewed", {
                activity_id: activity.id,
                is_professional: activity.isProfessional,
                min_price: activity.minPrice,
            });
        }
    }, [activity]);

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
