"use client";

import type { ReactNode } from "react";

import { useLocale, useTranslations } from "next-intl";
import { ChatRoundLine } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import { formatDay } from "@workspace/common";
import type { AvailabilityModel, PriceCalendar } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { AvailabilityCalendar } from "./availability-calendar";
import { PriceDisplay } from "./host-booking-bar";
import { HostBookingField } from "./host-booking-field";

type HostBookingBoxProps = {
    selector: ReactNode;
    usePetSelector: boolean;
    selectedAnimalCount: number;
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
    onBook: () => void;
    onContact?: () => void;
    isContactPending?: boolean;
};

function FieldHeader({ title, description }: { title: string; description: string }) {
    return (
        <div className="flex flex-col">
            <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
            <p className="text-sm text-muted-foreground">{description}</p>
        </div>
    );
}

export function HostBookingBox({
    selector,
    usePetSelector,
    selectedAnimalCount,
    dateRange,
    onDateRangeChange,
    availabilities,
    priceMap,
    onBook,
    onContact,
    isContactPending,
}: HostBookingBoxProps) {
    const t = useTranslations();
    const locale = useLocale();

    const datesValue =
        dateRange?.from && dateRange?.to
            ? `${formatDay(dateRange.from, locale)} – ${formatDay(dateRange.to, locale)}`
            : null;
    const animalsValue =
        selectedAnimalCount > 0
            ? t("features.host.detail.animalsCount", { count: selectedAnimalCount })
            : null;
    const animalsTitle = usePetSelector
        ? t("features.host.detail.estimateTitle")
        : t("features.host.detail.estimateTypeTitle");
    const animalsDescription = usePetSelector
        ? t("features.host.detail.estimateDescription")
        : t("features.host.detail.estimateTypeDescription");

    return (
        <div
            data-slot="host-booking-box"
            className="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
        >
            <div className="flex flex-col gap-2">
                <FieldHeader
                    title={t("features.host.detail.selectDate")}
                    description={t("features.host.detail.selectDateDescription")}
                />
                <HostBookingField
                    value={datesValue}
                    placeholder={t("features.host.detail.datesPlaceholder")}
                    contentClassName="w-auto"
                >
                    <div className="flex flex-col gap-2 p-3">
                        <AvailabilityCalendar
                            dateRange={dateRange}
                            onDateRangeChange={onDateRangeChange}
                            availabilities={availabilities}
                            priceMap={priceMap}
                            numberOfMonths={2}
                        />
                        {dateRange && (
                            <button
                                type="button"
                                className="self-start px-1 text-sm font-medium underline"
                                onClick={() => onDateRangeChange(undefined)}
                            >
                                {t("features.host.detail.clearDates")}
                            </button>
                        )}
                    </div>
                </HostBookingField>
            </div>

            <div className="flex flex-col gap-2">
                <FieldHeader title={animalsTitle} description={animalsDescription} />
                <HostBookingField
                    value={animalsValue}
                    placeholder={t("features.host.detail.animalsPlaceholder")}
                    contentClassName="w-80 p-4"
                >
                    {selector}
                </HostBookingField>
            </div>

            <Separator />

            <PriceDisplay priceMap={priceMap} dateRange={dateRange} />

            <div className="flex flex-col gap-2">
                <Button
                    size="lg"
                    onClick={onBook}
                    className="w-full rounded-full bg-foreground text-base font-medium text-background hover:bg-foreground/90"
                >
                    {t("features.host.detail.book")}
                </Button>
                {onContact && (
                    <Button
                        size="lg"
                        variant="outline"
                        onClick={onContact}
                        disabled={isContactPending}
                        className="w-full rounded-full"
                    >
                        <ChatRoundLine />
                        {t("features.conversations.contactHost")}
                    </Button>
                )}
            </div>
        </div>
    );
}
