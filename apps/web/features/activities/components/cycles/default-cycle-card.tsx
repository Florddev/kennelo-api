"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";

import {
    upsertClosedWeekDays,
    upsertCycleSettings,
    type ActivityCycleModel,
} from "@workspace/modules/activities";
import { getAnimalTypes } from "@workspace/modules/pets";
import { Button } from "@workspace/ui/components/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@workspace/ui/components/card";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCyclesQueryKey } from "../../hooks/use-activity-cycles";
import {
    cycleToMatrix,
    matrixClosedMask,
    matrixToSettingsInput,
    type CycleMatrixValue,
} from "../../lib/cycle-helpers";
import { CycleMatrixEditor } from "./cycle-matrix-editor";

type DefaultCycleCardProps = {
    activityId: string;
    cycle: ActivityCycleModel;
};

export function DefaultCycleCard({ activityId, cycle }: DefaultCycleCardProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();

    const [matrix, setMatrix] = useState<CycleMatrixValue>(() => cycleToMatrix(cycle));

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const handleSave = () =>
        execute(
            async () => {
                await upsertCycleSettings(activityId, cycle.id, matrixToSettingsInput(matrix));
                return upsertClosedWeekDays(activityId, cycle.id, {
                    sumWeekdays: matrixClosedMask(matrix),
                });
            },
            {
                onSuccess: () =>
                    queryClient.invalidateQueries({
                        queryKey: activityCyclesQueryKey(activityId),
                    }),
            },
        );

    return (
        <Card data-slot="default-cycle-card">
            <CardHeader>
                <CardTitle>{t("features.activities.cycles.defaultSettings.title")}</CardTitle>
                <CardDescription>
                    {t("features.activities.cycles.defaultSettings.description")}
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <CycleMatrixEditor animalTypes={animalTypes} value={matrix} onChange={setMatrix} />
                <Button className="self-end rounded-4xl" onClick={handleSave} disabled={isLoading}>
                    {isLoading ? t("common.actions.loading") : t("common.actions.save")}
                </Button>
            </CardContent>
        </Card>
    );
}
