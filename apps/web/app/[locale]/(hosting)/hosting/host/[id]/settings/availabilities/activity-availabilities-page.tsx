"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityAvailabilitiesList } from "@/features/activities/components/activity-availabilities-list";

export default function ActivityAvailabilitiesPage() {
    const { params } = useNavigation<{ id: string }>();

    return <ActivityAvailabilitiesList activityId={params.id} />;
}
