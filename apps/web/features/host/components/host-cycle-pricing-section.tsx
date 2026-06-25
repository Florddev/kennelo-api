"use client";

import { useLocale, useTranslations } from "next-intl";
import { CalendarDays } from "lucide-react";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { ALL_WEEKDAYS, formatDateRangeCompact, weekdayKeysFromMask } from "@workspace/common";
import type { ActivityCycleModel, ActivityCycleSettingModel } from "@workspace/modules/activities";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type HostCyclePricingSectionProps = {
    cycles: ActivityCycleModel[];
    isLoading: boolean;
};

export function HostCyclePricingSection({ cycles, isLoading }: HostCyclePricingSectionProps) {
    const t = useTranslations();
    const locale = useLocale();

    function cyclePeriodLabel(cycle: ActivityCycleModel): string {
        if (cycle.startDate && cycle.endDate) {
            return formatDateRangeCompact(cycle.startDate, cycle.endDate, locale);
        }
        return t("features.host.detail.cycleAllYear");
    }

    function weekdaysLabel(setting: ActivityCycleSettingModel): string {
        if (setting.sumWeekdays === ALL_WEEKDAYS) {
            return t("features.host.detail.cycleAllDays");
        }
        return weekdayKeysFromMask(setting.sumWeekdays)
            .map((key) => t(`features.activities.cycles.weekdaysShort.${key}`))
            .join(", ");
    }

    function renderBody() {
        if (isLoading) {
            return (
                <div className="flex flex-col gap-3">
                    {Array.from({ length: 2 }).map((_, index) => (
                        <Skeleton key={index} className="h-28 w-full rounded-2xl" />
                    ))}
                </div>
            );
        }

        if (cycles.length === 0) {
            return (
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <CalendarDays />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.cyclePricingEmpty")}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            );
        }

        return (
            <div className="flex flex-col gap-3">
                {cycles.map((cycle) => (
                    <div key={cycle.id} className="flex flex-col gap-3 rounded-2xl border p-4">
                        <div className="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                            <CalendarDays className="size-4 text-primary" />
                            {cyclePeriodLabel(cycle)}
                        </div>
                        {cycle.settings.length === 0 ? (
                            <p className="text-xs text-slate-500">
                                {t("features.host.detail.cyclePricingEmpty")}
                            </p>
                        ) : (
                            <div className="flex flex-col gap-2.5">
                                {cycle.settings.map((setting) => (
                                    <div
                                        key={setting.id}
                                        className="flex items-center justify-between gap-3"
                                    >
                                        <div className="flex min-w-0 items-center gap-2">
                                            <PetTypeIllustration
                                                code={setting.animalType.code}
                                                name={setting.animalType.name}
                                                className="size-7 shrink-0"
                                            />
                                            <div className="flex min-w-0 flex-col">
                                                <span className="truncate text-sm font-medium text-slate-700">
                                                    {setting.animalType.name}
                                                </span>
                                                <span className="truncate text-xs text-slate-500">
                                                    {weekdaysLabel(setting)}
                                                </span>
                                            </div>
                                        </div>
                                        <span className="shrink-0 text-sm text-slate-900">
                                            <span className="font-semibold">
                                                {Math.round(setting.price)} €
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {t("features.host.detail.perNight")}
                                            </span>
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        );
    }

    return (
        <section data-slot="host-cycle-pricing-section" className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.cyclePricingTitle")}
            </h2>

            {renderBody()}
        </section>
    );
}
