"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Check, CircleAlert } from "lucide-react";

import {
    upsertClosedWeekDays,
    upsertCycleSettings,
    type ActivityCycleModel,
} from "@workspace/modules/activities";
import { getAnimalTypes } from "@workspace/modules/pets";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@workspace/ui/components/card";
import { Spinner } from "@workspace/ui/components/spinner";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCyclesQueryKey } from "../../hooks/use-activity-cycles";
import {
    cycleToMatrix,
    matrixClosedMask,
    matrixToSettingsInput,
    type CycleMatrixValue,
} from "../../lib/cycle-helpers";
import { CycleMatrixEditor } from "./cycle-matrix-editor";

const SAVE_DEBOUNCE_MS = 700;

type SaveStatus = "idle" | "saving" | "saved" | "error";

type DefaultCycleCardProps = {
    activityId: string;
    cycle: ActivityCycleModel;
};

export function DefaultCycleCard({ activityId, cycle }: DefaultCycleCardProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute } = useAsyncState();

    const [matrix, setMatrix] = useState<CycleMatrixValue>(() => cycleToMatrix(cycle));
    const [status, setStatus] = useState<SaveStatus>("idle");
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const save = useCallback(
        (next: CycleMatrixValue) => {
            setStatus("saving");
            execute(
                async () => {
                    await upsertCycleSettings(activityId, cycle.id, matrixToSettingsInput(next));
                    return upsertClosedWeekDays(activityId, cycle.id, {
                        sumWeekdays: matrixClosedMask(next),
                    });
                },
                {
                    displayError: true,
                    onSuccess: () => {
                        setStatus("saved");
                        queryClient.invalidateQueries({
                            queryKey: activityCyclesQueryKey(activityId),
                        });
                    },
                    onFailure: () => setStatus("error"),
                },
            );
        },
        [activityId, cycle.id, execute, queryClient],
    );

    const handleChange = (next: CycleMatrixValue) => {
        setMatrix(next);
        setStatus("saving");
        if (timeoutRef.current) clearTimeout(timeoutRef.current);
        timeoutRef.current = setTimeout(() => save(next), SAVE_DEBOUNCE_MS);
    };

    useEffect(
        () => () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        },
        [],
    );

    useEffect(() => {
        if (status !== "saved") return;
        const id = setTimeout(() => setStatus("idle"), 2000);
        return () => clearTimeout(id);
    }, [status]);

    return (
        <Card data-slot="default-cycle-card">
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <CardTitle>{t("features.activities.cycles.defaultSettings.title")}</CardTitle>
                    <SaveIndicator status={status} />
                </div>
                <CardDescription>
                    {t("features.activities.cycles.defaultSettings.description")}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <CycleMatrixEditor
                    animalTypes={animalTypes}
                    value={matrix}
                    onChange={handleChange}
                />
            </CardContent>
        </Card>
    );
}

function SaveIndicator({ status }: { status: SaveStatus }) {
    const t = useTranslations();

    if (status === "idle") {
        return null;
    }

    if (status === "saving") {
        return (
            <span className="flex shrink-0 items-center gap-1.5 text-xs text-muted-foreground">
                <Spinner className="size-3.5" />
                {t("features.activities.cycles.autosave.saving")}
            </span>
        );
    }

    if (status === "error") {
        return (
            <span className="flex shrink-0 items-center gap-1.5 text-xs text-destructive">
                <CircleAlert className="size-3.5" />
                {t("features.activities.cycles.autosave.error")}
            </span>
        );
    }

    return (
        <span className="flex shrink-0 items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
            <Check className="size-3.5" />
            {t("features.activities.cycles.autosave.saved")}
        </span>
    );
}
