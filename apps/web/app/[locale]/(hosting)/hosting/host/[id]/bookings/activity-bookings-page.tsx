"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityBookingsTable } from "@/features/activities/components/activity-bookings-table";

export default function ActivityBookingsPage() {
    const { params } = useNavigation<{ id: string }>();

    return <ActivityBookingsTable activityId={params.id} />;
}
