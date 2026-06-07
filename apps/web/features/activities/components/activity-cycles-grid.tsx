"use client";

import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { CalendarRange, Plus } from "lucide-react";

import { createCycle } from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";

import { useAsyncState } from "@/hooks/use-async-state";
import { useActivityCycles, activityCyclesQueryKey } from "../hooks/use-activity-cycles";
import { CycleCard } from "./cycle-card";
import { ActivityPageHeader } from "./activity-page-header";

export function ActivityCyclesGrid({ activityId }: { activityId: string }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { cycles, isLoading } = useActivityCycles(activityId);
    const { execute, isLoading: isCreating } = useAsyncState();

    if (isLoading) {
        return null;
    }

    const handleAddCycle = () =>
        execute(() => createCycle(activityId, {}), {
            onSuccess: () =>
                queryClient.invalidateQueries({ queryKey: activityCyclesQueryKey(activityId) }),
        });

    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader>
                <Button
                    className="gap-1.5 rounded-4xl"
                    onClick={handleAddCycle}
                    disabled={isCreating}
                >
                    <Plus className="size-4" />
                    {t("features.activities.cycles.addCycle")}
                </Button>
            </ActivityPageHeader>

            {cycles.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed py-16 text-center">
                    <div className="mb-4 flex size-12 items-center justify-center rounded-full bg-muted">
                        <CalendarRange className="size-6 text-muted-foreground" />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {t("features.activities.cycles.empty")}
                    </p>
                </div>
            ) : (
                <div className="flex flex-col gap-4">
                    {cycles.map((cycle) => (
                        <CycleCard key={cycle.id} cycle={cycle} activityId={activityId} />
                    ))}
                </div>
            )}
        </div>
    );
}
