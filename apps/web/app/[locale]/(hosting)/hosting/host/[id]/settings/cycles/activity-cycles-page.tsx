"use client";

import { useState } from "react";

import type { ActivityCycleModel } from "@workspace/modules/activities";

import { useNavigation } from "@/hooks/use-navigation";
import { useActivityCycles } from "@/features/activities/hooks/use-activity-cycles";
import { CycleCalendar } from "@/features/activities/components/cycles/cycle-calendar";
import { CycleDialog } from "@/features/activities/components/cycles/cycle-dialog";
import { CycleTimeline } from "@/features/activities/components/cycles/cycle-timeline";
import { DefaultCycleCard } from "@/features/activities/components/cycles/default-cycle-card";
import { isDefaultCycle } from "@/features/activities/lib/cycle-helpers";

export default function ActivityCyclesPage() {
    const { params } = useNavigation<{ id: string }>();
    const activityId = params.id;

    const { cycles, isLoading } = useActivityCycles(activityId);

    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<ActivityCycleModel | null>(null);

    if (isLoading) {
        return null;
    }

    const defaultCycle = cycles.find(isDefaultCycle) ?? null;

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (cycle: ActivityCycleModel) => {
        setEditing(cycle);
        setDialogOpen(true);
    };

    return (
        <div className="flex flex-col gap-6">
            {defaultCycle ? (
                <DefaultCycleCard activityId={activityId} cycle={defaultCycle} />
            ) : null}

            <CycleCalendar activityId={activityId} cycles={cycles} />

            <CycleTimeline
                activityId={activityId}
                cycles={cycles}
                onCreate={openCreate}
                onEdit={openEdit}
            />

            <CycleDialog
                activityId={activityId}
                cycle={editing}
                defaultCycle={defaultCycle}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </div>
    );
}
