"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityCyclesGrid } from "@/features/activities/components/activity-cycles-grid";

export default function ActivityCapacitiesPage() {
    const { params } = useNavigation<{ id: string }>();

    return <ActivityCyclesGrid activityId={params.id} />;
}
