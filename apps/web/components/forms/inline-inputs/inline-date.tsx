"use client";

import React, { useState } from "react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";
import { AltArrowRight } from "@solar-icons/react";
import { useLocale, useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import { Calendar } from "@workspace/ui/components/calendar";
import { DateScrollPicker } from "@workspace/ui/components/date-scroll-picker";
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { NumberScrollPicker } from "@workspace/ui/components/number-scroll-picker";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { cn } from "@workspace/ui/lib/utils";

import { useIsMobile } from "@/hooks/use-mobile";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField, ageToIso, isoToAge, rowCn } from "./shared";

function DateModeToggle({
    mode,
    onChange,
    className,
}: {
    mode: "exact" | "approximate";
    onChange: (mode: "exact" | "approximate") => void;
    className?: string;
}) {
    const t = useTranslations();
    return (
        <div className={cn("flex gap-1 p-1 bg-muted rounded-xl", className)}>
            <Button
                type="button"
                variant={mode === "exact" ? "default" : "ghost"}
                size="sm"
                className="flex-1 text-xs"
                onClick={() => onChange("exact")}
            >
                {t("common.fields.exactDate")}
            </Button>
            <Button
                type="button"
                variant={mode === "approximate" ? "default" : "ghost"}
                size="sm"
                className="flex-1 text-xs"
                onClick={() => onChange("approximate")}
            >
                {t("common.fields.approximateAge")}
            </Button>
        </div>
    );
}

export type InlineDateProps = InlineFieldBaseProps<string> & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
    allowApproximate?: boolean;
};

export function InlineDate({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
    label,
    Icon,
    placeholder,
    isLoading,
    allowApproximate,
    className,
}: InlineDateProps) {
    const [open, setOpen] = useState(false);
    const [dateMode, setDateMode] = useState<"exact" | "approximate">("exact");
    const [approxYears, setApproxYears] = useState(0);
    const [approxMonths, setApproxMonths] = useState(0);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const locale = useLocale();
    const resolved = resolveInlineField<string>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });

    const selectedDate = resolved.value ? new Date(`${resolved.value}T00:00:00`) : undefined;
    const formattedDate = selectedDate
        ? new Intl.DateTimeFormat(undefined, {
              year: "numeric",
              month: "long",
              day: "numeric",
          }).format(selectedDate)
        : null;

    const handleDateSelect = (date: Date | undefined) => {
        if (!date) {
            resolved.onChange("");
        } else {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, "0");
            const d = String(date.getDate()).padStart(2, "0");
            resolved.onChange(`${y}-${m}-${d}`);
        }
        setOpen(false);
    };

    function handleModeChange(mode: "exact" | "approximate") {
        if (mode === "approximate" && resolved.value) {
            const { years, months } = isoToAge(resolved.value);
            setApproxYears(years);
            setApproxMonths(months);
        }
        setDateMode(mode);
    }

    const todayIso = new Date().toISOString().slice(0, 10);

    const trigger = (
        <div
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={rowCn(resolved.showError, isLoading, className, true)}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-1.5 items-center text-sm shrink-0">
                <span className={cn(!formattedDate && "text-muted-foreground")}>
                    {formattedDate ?? placeholder}
                </span>
                <AltArrowRight className="size-3.5" />
            </div>
        </div>
    );

    const approximatePickers = (size: "default" | "lg") => (
        <div className="flex flex-col justify-around w-full gap-4">
            <NumberScrollPicker
                value={approxYears}
                onChange={(value) => {
                    const years = value as number;
                    setApproxYears(years);
                    resolved.onChange(ageToIso(years, approxMonths));
                }}
                min={0}
                max={25}
                label={t("common.fields.years")}
                sideItems={3}
                size={size}
            />
            <NumberScrollPicker
                value={approxMonths}
                onChange={(value) => {
                    const months = value as number;
                    setApproxMonths(months);
                    resolved.onChange(ageToIso(approxYears, months));
                }}
                min={0}
                max={11}
                label={t("common.fields.months")}
                sideItems={3}
                size={size}
            />
        </div>
    );

    const calendar = (
        <Calendar
            mode="single"
            captionLayout="dropdown"
            startMonth={new Date(1950, 0)}
            endMonth={new Date()}
            selected={selectedDate}
            onSelect={handleDateSelect}
            disabled={{ after: new Date() }}
        />
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="pb-0">
                        <DrawerTitle>{t("common.fields.selectDate")}</DrawerTitle>
                    </DrawerHeader>
                    {allowApproximate && (
                        <div className="p-2">
                            <DateModeToggle mode={dateMode} onChange={handleModeChange} />
                        </div>
                    )}
                    <div className="flex justify-center py-4 px-1">
                        {dateMode === "exact" ? (
                            <DateScrollPicker
                                value={resolved.value || todayIso}
                                onChange={resolved.onChange}
                                minYear={1970}
                                maxYear={new Date().getFullYear()}
                                monthFormat="long"
                                locale={locale}
                                labels={{
                                    day: t("common.fields.day"),
                                    month: t("common.fields.month"),
                                    year: t("common.fields.year"),
                                }}
                                orientation="vertical"
                                className="justify-around w-full"
                                size="default"
                            />
                        ) : (
                            approximatePickers("lg")
                        )}
                    </div>
                    <DrawerFooter className="p-0">
                        <DrawerClose asChild>
                            <Button type="button" size="xl">
                                {t("common.actions.confirm")}
                            </Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className={cn("p-0", dateMode === "approximate" && "w-72")}>
                {allowApproximate && (
                    <DateModeToggle mode={dateMode} onChange={handleModeChange} className="m-2" />
                )}
                {dateMode === "exact" ? (
                    calendar
                ) : (
                    <div className="py-6 px-4">{approximatePickers("default")}</div>
                )}
            </PopoverContent>
        </Popover>
    );
}
