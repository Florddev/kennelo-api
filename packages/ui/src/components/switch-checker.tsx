"use client";

import { CheckIcon, XIcon } from "lucide-react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@workspace/ui/lib/utils";
import { Switch } from "./switch";

const switchCheckerVariants = cva("relative shrink-0", {
    variants: {
        size: {
            xs: "h-5 w-10",
            sm: "h-6 w-12",
            default: "h-7 w-14",
            lg: "h-8 w-16",
        },
    },
    defaultVariants: {
        size: "default",
    },
});

const sizeConfig = {
    xs: {
        switchWidth: "data-[size=default]:w-10",
        thumbSize: "[&_span]:group-data-[size=default]/switch:size-4",
        thumbTranslate:
            "[&_span]:data-[state=checked]:translate-x-5.5 [&_span]:data-[state=checked]:rtl:-translate-x-5",
        iconArea: "w-5",
        iconSize: "size-3",
    },
    sm: {
        switchWidth: "data-[size=default]:w-12",
        thumbSize: "[&_span]:group-data-[size=default]/switch:size-5",
        thumbTranslate:
            "[&_span]:data-[state=checked]:translate-x-6.5 [&_span]:data-[state=checked]:rtl:-translate-x-6",
        iconArea: "w-6",
        iconSize: "size-3",
    },
    default: {
        switchWidth: "data-[size=default]:w-14",
        thumbSize: "[&_span]:group-data-[size=default]/switch:size-6.5",
        thumbTranslate:
            "[&_span]:data-[state=checked]:translate-x-7 [&_span]:data-[state=checked]:rtl:-translate-x-7",
        iconArea: "w-7",
        iconSize: "size-4",
    },
    lg: {
        switchWidth: "data-[size=default]:w-16",
        thumbSize: "[&_span]:group-data-[size=default]/switch:size-7",
        thumbTranslate:
            "[&_span]:data-[state=checked]:translate-x-8.5 [&_span]:data-[state=checked]:rtl:-translate-x-8",
        iconArea: "w-8",
        iconSize: "size-5",
    },
} as const;

function SwitchChecker({
    checked,
    onCheckedChange,
    disabled,
    id,
    className,
    size = "default",
}: VariantProps<typeof switchCheckerVariants> & {
    checked?: boolean;
    onCheckedChange?: (checked: boolean) => void;
    disabled?: boolean;
    id?: string;
    className?: string;
}) {
    const config = sizeConfig[size ?? "default"];

    return (
        <div data-slot="switch-checker" className={cn(switchCheckerVariants({ size }), className)}>
            <Switch
                id={id}
                checked={checked}
                onCheckedChange={onCheckedChange}
                disabled={disabled}
                className={cn(
                    "peer absolute inset-0 bg-primary data-[state=unchecked]:bg-muted data-[size=default]:h-[inherit]",
                    config.switchWidth,
                    config.thumbSize,
                    config.thumbTranslate,
                    "[&_span]:z-10 [&_span]:bg-white [&_span]:transition-transform [&_span]:duration-300 [&_span]:ease-[cubic-bezier(0.16,1,0.3,1)]",
                )}
            />
            <span
                className={cn(
                    "pointer-events-none absolute inset-y-0 end-0 flex items-center justify-center transition-opacity duration-300 peer-data-[state=checked]:opacity-0",
                    config.iconArea,
                )}
            >
                <XIcon className={cn(config.iconSize)} aria-hidden="true" />
            </span>
            <span
                className={cn(
                    "pointer-events-none absolute inset-y-0 start-0 flex items-center justify-center text-background transition-opacity duration-300 peer-data-[state=unchecked]:opacity-0",
                    config.iconArea,
                )}
            >
                <CheckIcon className={config.iconSize} aria-hidden="true" />
            </span>
        </div>
    );
}

export { SwitchChecker };
