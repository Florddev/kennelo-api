"use client";

import { useMemo, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { ChevronLeft, ChevronRight, GripVertical, Plus } from "lucide-react";
import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    PointerSensor,
    useSensor,
    useSensors,
} from "@dnd-kit/core";
import {
    arrayMove,
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";

import { reorderCycles, type ActivityCycleModel } from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCyclesQueryKey } from "../../hooks/use-activity-cycles";
import { isDefaultCycle } from "../../lib/cycle-helpers";

const DAY_MS = 24 * 60 * 60 * 1000;
const DEFAULT_BAR_COLOR = "var(--color-primary)";

function dayOfYear(date: Date, yearStart: Date): number {
    return Math.round((date.getTime() - yearStart.getTime()) / DAY_MS);
}

function yearSpan(cycle: ActivityCycleModel, year: number): { left: number; width: number } | null {
    const yearStart = new Date(year, 0, 1);
    const yearEnd = new Date(year, 11, 31);
    const totalDays = dayOfYear(yearEnd, yearStart) + 1;

    const start = cycle.startDate ? new Date(`${cycle.startDate}T00:00:00`) : yearStart;
    const end = cycle.endDate ? new Date(`${cycle.endDate}T00:00:00`) : yearEnd;

    if (end < yearStart || start > yearEnd) {
        return null;
    }

    const clampStart = start < yearStart ? yearStart : start;
    const clampEnd = end > yearEnd ? yearEnd : end;

    const left = (dayOfYear(clampStart, yearStart) / totalDays) * 100;
    const width =
        ((dayOfYear(clampEnd, yearStart) - dayOfYear(clampStart, yearStart) + 1) / totalDays) * 100;

    return { left, width };
}

type CycleTimelineProps = {
    activityId: string;
    cycles: ActivityCycleModel[];
    onCreate: () => void;
    onEdit: (cycle: ActivityCycleModel) => void;
};

export function CycleTimeline({ activityId, cycles, onCreate, onEdit }: CycleTimelineProps) {
    const t = useTranslations();
    const locale = useLocale();
    const queryClient = useQueryClient();
    const { execute } = useAsyncState();

    const [year, setYear] = useState(new Date().getFullYear());

    const timelineCycles = useMemo(
        () =>
            cycles
                .filter((cycle) => !isDefaultCycle(cycle))
                .sort((a, b) => b.priority - a.priority),
        [cycles],
    );

    const orderedIds = timelineCycles.map((cycle) => cycle.id);
    const idsKey = orderedIds.join(",");

    const [order, setOrder] = useState<string[]>(orderedIds);
    const [syncedKey, setSyncedKey] = useState(idsKey);

    if (idsKey !== syncedKey) {
        setSyncedKey(idsKey);
        setOrder(orderedIds);
    }

    const cycleById = useMemo(() => new Map(cycles.map((cycle) => [cycle.id, cycle])), [cycles]);

    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));

    const months = useMemo(
        () =>
            Array.from({ length: 12 }).map((_, index) =>
                new Date(year, index, 1).toLocaleDateString(locale, { month: "short" }),
            ),
        [year, locale],
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const oldIndex = order.indexOf(String(active.id));
        const newIndex = order.indexOf(String(over.id));
        if (oldIndex === -1 || newIndex === -1) return;

        const next = arrayMove(order, oldIndex, newIndex);
        setOrder(next);

        execute(() => reorderCycles(activityId, next), {
            onSuccess: () =>
                queryClient.invalidateQueries({ queryKey: activityCyclesQueryKey(activityId) }),
        });
    };

    return (
        <Card data-slot="cycle-timeline">
            <CardHeader className="gap-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>{t("features.activities.cycles.timeline.title")}</CardTitle>
                    <Button className="gap-1.5 rounded-4xl" onClick={onCreate}>
                        <Plus className="size-4" />
                        {t("features.activities.cycles.timeline.addCycle")}
                    </Button>
                </div>
                <div className="flex items-center justify-between">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => setYear(year - 1)}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <span className="text-sm font-medium tabular-nums">{year}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => setYear(year + 1)}
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                <div className="flex ps-7">
                    {months.map((label, index) => (
                        <div
                            key={index}
                            className="flex-1 text-center text-[10px] uppercase text-muted-foreground"
                        >
                            {label}
                        </div>
                    ))}
                </div>

                {order.length === 0 ? (
                    <p className="py-8 text-center text-sm text-muted-foreground">
                        {t("features.activities.cycles.timeline.empty")}
                    </p>
                ) : (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragEnd={handleDragEnd}
                    >
                        <SortableContext items={order} strategy={verticalListSortingStrategy}>
                            <div className="flex flex-col gap-2">
                                {order.map((id) => {
                                    const cycle = cycleById.get(id);
                                    if (!cycle) return null;
                                    return (
                                        <SortableCycleRow
                                            key={id}
                                            cycle={cycle}
                                            year={year}
                                            label={t(
                                                "features.activities.cycles.timeline.cycleLabel",
                                                {
                                                    priority: cycle.priority,
                                                },
                                            )}
                                            onEdit={() => onEdit(cycle)}
                                        />
                                    );
                                })}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}
            </CardContent>
        </Card>
    );
}

type SortableCycleRowProps = {
    cycle: ActivityCycleModel;
    year: number;
    label: string;
    onEdit: () => void;
};

function SortableCycleRow({ cycle, year, label, onEdit }: SortableCycleRowProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: cycle.id,
    });

    const span = yearSpan(cycle, year);
    const color = cycle.color ?? DEFAULT_BAR_COLOR;

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn("flex items-center gap-2 rounded-2xl", isDragging && "z-10 opacity-80")}
        >
            <button
                type="button"
                className="cursor-grab text-muted-foreground active:cursor-grabbing"
                aria-label={label}
                {...attributes}
                {...listeners}
            >
                <GripVertical className="size-4" />
            </button>
            <div className="relative h-9 flex-1 overflow-hidden rounded-2xl bg-muted/40">
                {span ? (
                    <button
                        type="button"
                        onClick={onEdit}
                        className="absolute inset-y-1 flex items-center rounded-xl px-2 text-xs font-medium text-white shadow-sm"
                        style={{
                            insetInlineStart: `${span.left}%`,
                            width: `${span.width}%`,
                            backgroundColor: color,
                        }}
                    >
                        <span className="truncate">{label}</span>
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={onEdit}
                        className="absolute inset-0 flex items-center justify-center text-xs text-muted-foreground"
                    >
                        {label}
                    </button>
                )}
            </div>
        </div>
    );
}
