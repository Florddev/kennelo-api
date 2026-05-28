"use client";

import * as React from "react";
import { useMemo } from "react";

import { ScrollPicker, type ScrollPickerSize } from "@workspace/ui/components/scroll-picker";
import { cn } from "@workspace/ui/lib/utils";

export type NumberScrollPickerProps = {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    step?: number;
    unit?: string;
    label?: string;
    orientation?: "horizontal" | "vertical";
    size?: ScrollPickerSize;
    itemSize?: number;
    itemWidth?: number;
    sideItems?: number;
    className?: string;
};

function NumberScrollPicker({
    value,
    onChange,
    min = 0,
    max = 99,
    step = 1,
    unit,
    label,
    orientation = "horizontal",
    size = "sm",
    itemSize,
    itemWidth,
    sideItems = 2,
    className,
}: NumberScrollPickerProps) {
    const items = useMemo(
        () =>
            Array.from({ length: Math.floor((max - min) / step) + 1 }, (_, i) => {
                const val = parseFloat((min + i * step).toFixed(10));
                return { label: String(val), value: val };
            }),
        [min, max, step],
    );

    return (
        <div
            data-slot="number-scroll-picker"
            className={cn("flex flex-col items-center gap-1", className)}
        >
            {label && (
                <span className="text-xs font-medium text-muted-foreground tracking-wide">
                    {label}
                </span>
            )}
            <div className="flex items-center gap-1">
                <ScrollPicker
                    items={items}
                    value={value}
                    onChange={(v) => onChange(v as number)}
                    orientation={orientation}
                    size={size}
                    itemSize={itemSize}
                    itemWidth={itemWidth}
                    sideItems={sideItems}
                />
                {unit && (
                    <span className="text-sm font-medium text-muted-foreground shrink-0">
                        {unit}
                    </span>
                )}
            </div>
        </div>
    );
}

export { NumberScrollPicker };
