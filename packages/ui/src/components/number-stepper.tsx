"use client";

import { Minus, Plus } from "lucide-react";
import * as React from "react";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

function NumberStepper({
    value,
    onIncrement,
    onDecrement,
    min,
    max,
    step = 1,
    disabled = false,
    label,
    formatValue,
    className,
    ...props
}: Omit<React.ComponentProps<"div">, "onChange"> & {
    value: number;
    onIncrement: () => void;
    onDecrement: () => void;
    min?: number;
    max?: number;
    step?: number;
    disabled?: boolean;
    label?: string;
    formatValue?: (value: number) => string;
}) {
    const canDecrement = !disabled && (min === undefined || value - step >= min);
    const canIncrement = !disabled && (max === undefined || value + step <= max);

    return (
        <div
            data-slot="number-stepper"
            className={cn("flex flex-col gap-1.5", className)}
            {...props}
        >
            {label && (
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {label}
                </span>
            )}
            <div className="flex items-center justify-between gap-3 rounded-2xl bg-muted p-1.5">
                <Button
                    type="button"
                    variant="default"
                    size="icon-sm"
                    onClick={onDecrement}
                    disabled={!canDecrement}
                >
                    <Minus className="size-5" />
                </Button>
                <span className="flex-1 text-center text-base font-semibold tabular-nums">
                    {formatValue ? formatValue(value) : value}
                </span>
                <Button
                    type="button"
                    variant="secondary"
                    size="icon-sm"
                    onClick={onIncrement}
                    disabled={!canIncrement}
                >
                    <Plus className="size-5" />
                </Button>
            </div>
        </div>
    );
}

export { NumberStepper };
