"use client";

import { useMemo, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { ChevronLeft, ChevronRight } from "lucide-react";

import {
    createAvailability,
    deleteAvailability,
    getAvailabilities,
    type ActivityCycleModel,
    type AvailabilityModel,
} from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { NativeSelect, NativeSelectOption } from "@workspace/ui/components/native-select";
import { cn } from "@workspace/ui/lib/utils";

import { useAsyncState } from "@/hooks/use-async-state";
import { priceForDate } from "../../lib/cycle-helpers";

function pad(value: number): string {
    return String(value).padStart(2, "0");
}

function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month, 0).getDate();
}

function getFirstWeekdayOffset(year: number, month: number): number {
    const day = new Date(year, month - 1, 1).getDay();
    return day === 0 ? 6 : day - 1;
}

type CycleCalendarProps = {
    activityId: string;
    cycles: ActivityCycleModel[];
};

export function CycleCalendar({ activityId, cycles }: CycleCalendarProps) {
    const t = useTranslations();
    const locale = useLocale();
    const queryClient = useQueryClient();
    const { execute } = useAsyncState();
    const now = new Date();

    const [year, setYear] = useState(now.getFullYear());
    const [month, setMonth] = useState(now.getMonth() + 1);
    const [animalTypeId, setAnimalTypeId] = useState<string>("");

    const activityTypes = useMemo(() => {
        const seen = new Set<string>();
        const result: { id: string; name: string }[] = [];

        for (const cycle of cycles) {
            for (const setting of cycle.settings) {
                if (!seen.has(setting.animalType.id)) {
                    seen.add(setting.animalType.id);
                    result.push({ id: setting.animalType.id, name: setting.animalType.name });
                }
            }
        }

        return result;
    }, [cycles]);

    const monthKey = `${year}-${pad(month)}`;
    const availabilitiesQueryKey = ["activity-availabilities", activityId, monthKey];

    const { data: availabilities = [] } = useQuery({
        queryKey: availabilitiesQueryKey,
        queryFn: () => getAvailabilities(activityId, monthKey),
        enabled: Boolean(activityId),
    });

    const selectedType = animalTypeId || activityTypes[0]?.id || "";

    const availabilityMap = useMemo(
        () => new Map<string, AvailabilityModel>(availabilities.map((item) => [item.date, item])),
        [availabilities],
    );

    const dayNames = useMemo(() => {
        const base = new Date(2024, 0, 1);
        return Array.from({ length: 7 }).map((_, index) => {
            const date = new Date(base);
            date.setDate(base.getDate() + index);
            return date.toLocaleDateString(locale, { weekday: "short" });
        });
    }, [locale]);

    const monthLabel = new Date(year, month - 1, 1).toLocaleDateString(locale, {
        month: "long",
        year: "numeric",
    });

    const goPrev = () => {
        if (month === 1) {
            setYear(year - 1);
            setMonth(12);
        } else {
            setMonth(month - 1);
        }
    };

    const goNext = () => {
        if (month === 12) {
            setYear(year + 1);
            setMonth(1);
        } else {
            setMonth(month + 1);
        }
    };

    const refresh = () => queryClient.invalidateQueries({ queryKey: availabilitiesQueryKey });

    const toggleDay = (dateStr: string, current: AvailabilityModel | undefined) => {
        if (current?.status === "closed") {
            execute(() => deleteAvailability(activityId, current.id), { onSuccess: refresh });
            return;
        }

        execute(
            () =>
                createAvailability(activityId, {
                    startDate: dateStr,
                    endDate: dateStr,
                    status: "closed",
                    note: "",
                }),
            { onSuccess: refresh },
        );
    };

    const daysInMonth = getDaysInMonth(year, month);
    const offset = getFirstWeekdayOffset(year, month);

    return (
        <Card data-slot="cycle-calendar">
            <CardHeader className="gap-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>{t("features.activities.cycles.calendar.title")}</CardTitle>
                    <NativeSelect
                        value={selectedType}
                        onChange={(event) => setAnimalTypeId(event.target.value)}
                    >
                        {activityTypes.map((type) => (
                            <NativeSelectOption key={type.id} value={type.id}>
                                {type.name}
                            </NativeSelectOption>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex items-center justify-between">
                    <Button variant="ghost" size="icon" className="size-8" onClick={goPrev}>
                        <ChevronLeft className="size-4" />
                    </Button>
                    <span className="text-sm font-medium capitalize">{monthLabel}</span>
                    <Button variant="ghost" size="icon" className="size-8" onClick={goNext}>
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-7 gap-1">
                    {dayNames.map((name, index) => (
                        <div
                            key={index}
                            className="py-2 text-center text-xs font-medium uppercase text-muted-foreground"
                        >
                            {name}
                        </div>
                    ))}
                    {Array.from({ length: offset }).map((_, index) => (
                        <div key={`offset-${index}`} />
                    ))}
                    {Array.from({ length: daysInMonth }).map((_, index) => {
                        const day = index + 1;
                        const dateStr = `${year}-${pad(month)}-${pad(day)}`;
                        const date = new Date(year, month - 1, day);
                        const price = selectedType
                            ? priceForDate(cycles, selectedType, date)
                            : null;
                        const availability = availabilityMap.get(dateStr);
                        const structurallyClosed = price === null;
                        const isClosed = structurallyClosed || availability?.status === "closed";

                        return (
                            <button
                                key={day}
                                type="button"
                                disabled={structurallyClosed}
                                onClick={() => toggleDay(dateStr, availability)}
                                className={cn(
                                    "flex aspect-square flex-col items-center justify-center gap-0.5 rounded-xl border p-1 text-sm transition-colors",
                                    isClosed
                                        ? "border-transparent bg-destructive/10 text-destructive"
                                        : "border-transparent bg-primary/10 text-primary hover:bg-primary/20",
                                    structurallyClosed && "opacity-50",
                                )}
                            >
                                <span className="tabular-nums">{day}</span>
                                {price !== null ? (
                                    <span className="text-[10px] font-medium tabular-nums">
                                        {t("features.activities.cycles.priceValue", { price })}
                                    </span>
                                ) : null}
                            </button>
                        );
                    })}
                </div>
                <div className="mt-4 flex items-center gap-6 text-xs text-muted-foreground">
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-primary/30" />
                        <span>{t("features.activities.availabilities.open")}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="size-3 rounded-full bg-destructive/30" />
                        <span>{t("features.activities.availabilities.closed")}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
