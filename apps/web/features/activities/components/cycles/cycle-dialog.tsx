"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Check, Trash2 } from "lucide-react";

import {
    createCycle,
    deleteCycle,
    updateCycle,
    upsertClosedWeekDays,
    upsertCycleSettings,
    type ActivityCycleModel,
} from "@workspace/modules/activities";
import { getAnimalTypes } from "@workspace/modules/pets";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@workspace/ui/components/alert-dialog";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import { Label } from "@workspace/ui/components/label";
import { cn } from "@workspace/ui/lib/utils";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCyclesQueryKey } from "../../hooks/use-activity-cycles";
import {
    cycleToMatrix,
    emptyMatrix,
    matrixClosedMask,
    matrixToSettingsInput,
    type CycleMatrixValue,
} from "../../lib/cycle-helpers";
import { CycleMatrixEditor } from "./cycle-matrix-editor";

export const CYCLE_COLORS = [
    "#ef4444",
    "#f97316",
    "#eab308",
    "#22c55e",
    "#14b8a6",
    "#3b82f6",
    "#8b5cf6",
    "#ec4899",
];

type CycleDialogProps = {
    activityId: string;
    cycle: ActivityCycleModel | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type CycleFormState = {
    startDate: string;
    endDate: string;
    color: string | null;
    matrix: CycleMatrixValue;
};

function buildFormState(cycle: ActivityCycleModel | null): CycleFormState {
    return {
        startDate: cycle?.startDate ?? "",
        endDate: cycle?.endDate ?? "",
        color: cycle?.color ?? null,
        matrix: cycle ? cycleToMatrix(cycle) : emptyMatrix(),
    };
}

export function CycleDialog({ activityId, cycle, open, onOpenChange }: CycleDialogProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const remove = useAsyncState();

    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [color, setColor] = useState<string | null>(null);
    const [matrix, setMatrix] = useState<CycleMatrixValue>(() => emptyMatrix());

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
        staleTime: 5 * 60_000,
    });

    const resetKey = open ? (cycle?.id ?? "new") : "closed";
    const [syncedKey, setSyncedKey] = useState(resetKey);

    if (resetKey !== syncedKey) {
        setSyncedKey(resetKey);
        if (open) {
            const next = buildFormState(cycle);
            setStartDate(next.startDate);
            setEndDate(next.endDate);
            setColor(next.color);
            setMatrix(next.matrix);
        }
    }

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: activityCyclesQueryKey(activityId) });

    const handleSave = () =>
        execute(
            async () => {
                const target = cycle
                    ? await updateCycle(activityId, cycle.id, { startDate, endDate, color })
                    : await createCycle(activityId, { startDate, endDate, color });

                await upsertCycleSettings(activityId, target.id, matrixToSettingsInput(matrix));

                return upsertClosedWeekDays(activityId, target.id, {
                    sumWeekdays: matrixClosedMask(matrix),
                });
            },
            {
                onSuccess: () => {
                    invalidate();
                    onOpenChange(false);
                },
            },
        );

    const handleDelete = () => {
        if (!cycle) return;
        remove.execute(() => deleteCycle(activityId, cycle.id), {
            onSuccess: () => {
                invalidate();
                onOpenChange(false);
            },
        });
    };

    const title = cycle
        ? t("features.activities.cycles.dialog.editTitle")
        : t("features.activities.cycles.dialog.createTitle");

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent className="max-w-3xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                </AlertDialogHeader>

                <div className="flex max-h-[70vh] flex-col gap-5 overflow-y-auto">
                    <div className="flex flex-wrap gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="cycle-start">
                                {t("features.activities.cycles.dialog.startDate")}
                            </Label>
                            <Input
                                id="cycle-start"
                                type="date"
                                value={startDate}
                                onChange={(event) => setStartDate(event.target.value)}
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="cycle-end">
                                {t("features.activities.cycles.dialog.endDate")}
                            </Label>
                            <Input
                                id="cycle-end"
                                type="date"
                                value={endDate}
                                min={startDate || undefined}
                                onChange={(event) => setEndDate(event.target.value)}
                            />
                        </div>
                    </div>

                    <ColorPicker value={color} onChange={setColor} />

                    <CycleMatrixEditor
                        animalTypes={animalTypes}
                        value={matrix}
                        onChange={setMatrix}
                    />
                </div>

                <AlertDialogFooter className="flex-row items-center justify-between gap-2 sm:justify-between">
                    {cycle ? (
                        <Button
                            variant="ghost"
                            className="gap-1.5 text-destructive hover:text-destructive"
                            onClick={handleDelete}
                            disabled={remove.isLoading}
                        >
                            <Trash2 className="size-4" />
                            {t("common.actions.delete")}
                        </Button>
                    ) : (
                        <span />
                    )}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            className="rounded-4xl"
                            onClick={() => onOpenChange(false)}
                        >
                            {t("common.actions.cancel")}
                        </Button>
                        <Button className="rounded-4xl" onClick={handleSave} disabled={isLoading}>
                            {isLoading ? t("common.actions.loading") : t("common.actions.save")}
                        </Button>
                    </div>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

type ColorPickerProps = {
    value: string | null;
    onChange: (color: string | null) => void;
};

function ColorPicker({ value, onChange }: ColorPickerProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-2">
            <Label>{t("features.activities.cycles.dialog.color")}</Label>
            <div className="flex flex-wrap items-center gap-2">
                {CYCLE_COLORS.map((swatch) => {
                    const active = value === swatch;
                    return (
                        <button
                            key={swatch}
                            type="button"
                            onClick={() => onChange(swatch)}
                            aria-label={swatch}
                            aria-pressed={active}
                            className={cn(
                                "flex size-8 items-center justify-center rounded-4xl border-2 transition-transform",
                                active ? "border-foreground" : "border-transparent",
                            )}
                            style={{ backgroundColor: swatch }}
                        >
                            {active ? <Check className="size-4 text-white" /> : null}
                        </button>
                    );
                })}
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="rounded-4xl"
                    onClick={() => onChange(null)}
                >
                    {t("features.activities.cycles.dialog.noColor")}
                </Button>
            </div>
        </div>
    );
}
