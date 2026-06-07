"use client";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityCollaboratorsTable } from "@/features/activities/components/activity-collaborators-table";

export default function ActivityCollaboratorsPage() {
    const { params } = useNavigation<{ id: string }>();

    return <ActivityCollaboratorsTable activityId={params.id} />;
}
