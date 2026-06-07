"use client";

import { useEffect, useRef, useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { CalendarRange, Settings2, Trash2 } from "lucide-react";
import { useTranslations } from "next-intl";

import {
    deleteCycle,
    updateCycle,
    upsertClosedWeekDays,
    type ActivityCycleModel,
} from "@workspace/modules/activities";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@workspace/ui/components/alert-dialog";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import { Label } from "@workspace/ui/components/label";
import { NumberStepper } from "@workspace/ui/components/number-stepper";
import { Switch } from "@workspace/ui/components/switch";
import { Separator } from "@workspace/ui/components/separator";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCyclesQueryKey } from "../hooks/use-activity-cycles";
import { CycleSettingsEditor } from "./cycle-settings-editor";
import { WeekdaySelector } from "./weekday-selector";

const SAVE_DEBOUNCE_MS = 600;
const MAX_PRIORITY = 999;

export function CycleCard({
    cycle,
    activityId,
}: {
    cycle: ActivityCycleModel;
    activityId: string;
}) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute } = useAsyncState();

    const [priority, setPriority] = useState(cycle.priority);
    const [syncedPriority, setSyncedPriority] = useState(cycle.priority);
    const saveTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    if (syncedPriority !== cycle.priority) {
        setSyncedPriority(cycle.priority);
        setPriority(cycle.priority);
    }

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: activityCyclesQueryKey(activityId) });

    const commit = (patch: {
        startDate?: string;
        endDate?: string;
        priority?: number;
        isActive?: boolean;
    }) => execute(() => updateCycle(activityId, cycle.id, patch), { onSuccess: invalidate });

    const schedulePriority = (next: number) => {
        setPriority(next);
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
        }
        saveTimeoutRef.current = setTimeout(() => commit({ priority: next }), SAVE_DEBOUNCE_MS);
    };

    useEffect(
        () => () => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }
        },
        [],
    );

    const handleDelete = () =>
        execute(() => deleteCycle(activityId, cycle.id), { onSuccess: invalidate });

    const handleClosedDays = (mask: number) =>
        execute(() => upsertClosedWeekDays(activityId, cycle.id, { sumWeekdays: mask }), {
            onSuccess: invalidate,
        });

    const periodLabel =
        cycle.startDate || cycle.endDate
            ? `${cycle.startDate ?? "…"} → ${cycle.endDate ?? "…"}`
            : t("features.activities.cycles.defaultPeriod");

    const closedMask = cycle.closedWeekDays[0]?.sumWeekdays ?? 0;

    return (
        <div data-slot="cycle-card" className="flex flex-col gap-4 rounded-2xl border bg-card p-4">
            <div className="flex items-start justify-between gap-2">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <CalendarRange className="size-4 text-muted-foreground" />
                        <span className="text-sm font-semibold">{periodLabel}</span>
                    </div>
                    <Badge variant={cycle.isActive ? "default" : "secondary"} className="w-fit">
                        {cycle.isActive
                            ? t("features.activities.cycles.active")
                            : t("features.activities.cycles.inactive")}
                    </Badge>
                </div>
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 text-muted-foreground hover:text-destructive"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {t("features.activities.cycles.deleteCycle")}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {t("features.activities.cycles.deleteConfirmation")}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>{t("common.actions.cancel")}</AlertDialogCancel>
                            <AlertDialogAction variant="destructive" onClick={handleDelete}>
                                {t("common.actions.delete")}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor={`start-${cycle.id}`}>
                        {t("features.activities.cycles.startDate")}
                    </Label>
                    <Input
                        id={`start-${cycle.id}`}
                        type="date"
                        defaultValue={cycle.startDate ?? ""}
                        onChange={(event) => commit({ startDate: event.target.value })}
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor={`end-${cycle.id}`}>
                        {t("features.activities.cycles.endDate")}
                    </Label>
                    <Input
                        id={`end-${cycle.id}`}
                        type="date"
                        defaultValue={cycle.endDate ?? ""}
                        onChange={(event) => commit({ endDate: event.target.value })}
                    />
                </div>
            </div>

            <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <Switch
                        id={`active-${cycle.id}`}
                        checked={cycle.isActive}
                        onCheckedChange={(checked) => commit({ isActive: checked })}
                    />
                    <Label htmlFor={`active-${cycle.id}`}>
                        {t("features.activities.cycles.activeLabel")}
                    </Label>
                </div>
                <NumberStepper
                    label={t("features.activities.cycles.priority")}
                    value={priority}
                    onIncrement={() => schedulePriority(priority + 1)}
                    onDecrement={() => schedulePriority(priority - 1)}
                    min={0}
                    max={MAX_PRIORITY}
                />
            </div>

            <Separator />

            <div className="flex flex-col gap-3">
                <div className="flex items-center justify-between gap-2">
                    <h3 className="text-sm font-semibold">
                        {t("features.activities.cycles.settingsTitle")}
                    </h3>
                    <CycleSettingsEditor
                        activityId={activityId}
                        cycle={cycle}
                        trigger={
                            <Button variant="outline" size="sm" className="gap-1.5 rounded-4xl">
                                <Settings2 className="size-4" />
                                {t("features.activities.cycles.editSettings")}
                            </Button>
                        }
                    />
                </div>
                {cycle.settings.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t("features.activities.cycles.settingsEmpty")}
                    </p>
                ) : (
                    <ul className="flex flex-col gap-1.5">
                        {cycle.settings.map((setting) => (
                            <li
                                key={setting.id}
                                className="flex items-center justify-between gap-2 text-sm"
                            >
                                <span className="font-medium">{setting.animalType.name}</span>
                                <span className="tabular-nums text-muted-foreground">
                                    {t("features.activities.cycles.settingSummary", {
                                        capacity: setting.maxCapacity,
                                        price: setting.price,
                                    })}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Separator />

            <div className="flex flex-col gap-2">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {t("features.activities.cycles.closedDays")}
                </span>
                <WeekdaySelector value={closedMask} onChange={handleClosedDays} />
            </div>
        </div>
    );
}
