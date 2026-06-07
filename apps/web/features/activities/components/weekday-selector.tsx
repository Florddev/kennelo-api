"use client";

import { useTranslations } from "next-intl";

import {
    WEEKDAY_KEYS,
    WEEKDAY_VALUES,
    weekdayMaskContains,
    type WeekDayKey,
} from "@workspace/common";
import { cn } from "@workspace/ui/lib/utils";

type WeekdaySelectorProps = {
    value: number;
    onChange: (mask: number) => void;
    className?: string;
};

export function WeekdaySelector({ value, onChange, className }: WeekdaySelectorProps) {
    const t = useTranslations();

    const toggle = (key: WeekDayKey) => {
        const dayValue = WEEKDAY_VALUES[key];
        onChange(weekdayMaskContains(value, dayValue) ? value & ~dayValue : value | dayValue);
    };

    return (
        <div data-slot="weekday-selector" className={cn("flex flex-wrap gap-1.5", className)}>
            {WEEKDAY_KEYS.map((key) => {
                const active = weekdayMaskContains(value, WEEKDAY_VALUES[key]);
                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => toggle(key)}
                        aria-pressed={active}
                        className={cn(
                            "size-9 rounded-4xl border text-xs font-medium transition-colors",
                            active
                                ? "border-primary bg-primary text-primary-foreground"
                                : "border-border bg-card text-muted-foreground hover:border-primary/50",
                        )}
                    >
                        {t(`features.activities.cycles.weekdaysShort.${key}`)}
                    </button>
                );
            })}
        </div>
    );
}
