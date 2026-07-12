"use client";

import Link from "next/link";
import { useLocale, useTranslations } from "next-intl";
import { BedDouble, LogIn, LogOut, PawPrint } from "lucide-react";

import { Badge } from "@workspace/ui/components/badge";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@workspace/ui/components/sheet";
import { cn } from "@workspace/ui/lib/utils";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { useNavigation } from "@/hooks/use-navigation";

import { statusColor } from "../lib/booking-colors";
import type { DayMovements, PetMovement } from "../lib/calendar-grid";

type MovementSectionKind = "arrival" | "departure" | "staying";

type SectionConfig = {
    kind: MovementSectionKind;
    Icon: typeof LogIn;
    iconClass: string;
    chipClass: string;
    titleKey: string;
    emptyKey: string;
};

const SECTIONS: SectionConfig[] = [
    {
        kind: "arrival",
        Icon: LogIn,
        iconClass: "text-emerald-600 dark:text-emerald-400",
        chipClass: "bg-emerald-500/10",
        titleKey: "features.hosting-calendar.sheet.sections.arrivals",
        emptyKey: "features.hosting-calendar.sheet.sections.emptyArrivals",
    },
    {
        kind: "departure",
        Icon: LogOut,
        iconClass: "text-rose-600 dark:text-rose-400",
        chipClass: "bg-rose-500/10",
        titleKey: "features.hosting-calendar.sheet.sections.departures",
        emptyKey: "features.hosting-calendar.sheet.sections.emptyDepartures",
    },
    {
        kind: "staying",
        Icon: BedDouble,
        iconClass: "text-sky-600 dark:text-sky-400",
        chipClass: "bg-sky-500/10",
        titleKey: "features.hosting-calendar.sheet.sections.staying",
        emptyKey: "features.hosting-calendar.sheet.sections.emptyStaying",
    },
];

type DayBookingsSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    selectedDate: Date | null;
    movements: DayMovements;
};

export function DayBookingsSheet({
    open,
    onOpenChange,
    selectedDate,
    movements,
}: DayBookingsSheetProps) {
    const locale = useLocale();
    const t = useTranslations();

    const dateLabel = selectedDate
        ? new Intl.DateTimeFormat(locale, {
              weekday: "long",
              day: "numeric",
              month: "long",
              year: "numeric",
          }).format(selectedDate)
        : "";

    const movementCount = movements.arrivals.length + movements.departures.length;
    const isEmpty = movements.occupancy === 0;

    const itemsByKind: Record<MovementSectionKind, PetMovement[]> = {
        arrival: movements.arrivals,
        departure: movements.departures,
        staying: movements.staying,
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full sm:max-w-md flex flex-col gap-0 p-0">
                <SheetHeader className="border-b gap-3">
                    <SheetTitle className="capitalize text-lg">{dateLabel}</SheetTitle>
                    <SheetDescription className="sr-only">
                        {t("features.hosting-calendar.sheet.recap", {
                            movements: movementCount,
                            staying: movements.staying.length,
                        })}
                    </SheetDescription>
                    <div className="flex flex-wrap gap-2">
                        <StatChip
                            Icon={PawPrint}
                            value={movements.occupancy}
                            label={t("features.hosting-calendar.sheet.hostedTotal")}
                            tone="border-primary/20 bg-primary/5 text-primary"
                        />
                        <StatChip
                            Icon={LogIn}
                            value={movements.arrivals.length}
                            label={t("features.hosting-calendar.sheet.sections.arrivals")}
                            tone="border-emerald-500/25 bg-emerald-500/5 text-emerald-600 dark:text-emerald-400"
                        />
                        <StatChip
                            Icon={LogOut}
                            value={movements.departures.length}
                            label={t("features.hosting-calendar.sheet.sections.departures")}
                            tone="border-rose-500/25 bg-rose-500/5 text-rose-600 dark:text-rose-400"
                        />
                        <StatChip
                            Icon={BedDouble}
                            value={movements.staying.length}
                            label={t("features.hosting-calendar.sheet.sections.staying")}
                            tone="border-sky-500/25 bg-sky-500/5 text-sky-600 dark:text-sky-400"
                        />
                    </div>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-6 flex flex-col gap-6">
                    {isEmpty ? (
                        <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                            <div className="flex items-center justify-center size-14 rounded-full bg-muted">
                                <PawPrint className="size-6 text-muted-foreground" />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {t("features.hosting-calendar.sheet.empty")}
                            </p>
                        </div>
                    ) : (
                        SECTIONS.map((section) => (
                            <MovementSection
                                key={section.kind}
                                config={section}
                                items={itemsByKind[section.kind]}
                            />
                        ))
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

function StatChip({
    Icon,
    value,
    label,
    tone,
}: {
    Icon: typeof LogIn;
    value: number;
    label: string;
    tone: string;
}) {
    return (
        <span
            title={label}
            className={cn(
                "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-sm font-semibold",
                tone,
            )}
        >
            <Icon className="size-3.5 shrink-0" />
            {value}
        </span>
    );
}

function MovementSection({ config, items }: { config: SectionConfig; items: PetMovement[] }) {
    const t = useTranslations();
    const { Icon } = config;

    return (
        <section className="flex flex-col gap-2.5" data-slot="movement-section">
            <header className="flex items-center gap-2">
                <span
                    className={cn(
                        "flex items-center justify-center size-8 rounded-xl",
                        config.chipClass,
                    )}
                >
                    <Icon className={cn("size-4", config.iconClass)} />
                </span>
                <h3 className="flex-1 text-sm font-semibold">
                    {t(config.titleKey as Parameters<typeof t>[0])}
                </h3>
                <span className="inline-flex items-center justify-center min-w-6 h-6 rounded-full bg-muted px-2 text-xs font-semibold text-muted-foreground">
                    {items.length}
                </span>
            </header>

            {items.length === 0 ? (
                <p className="rounded-2xl border border-dashed py-4 text-center text-xs text-muted-foreground">
                    {t(config.emptyKey as Parameters<typeof t>[0])}
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {items.map((movement) => (
                        <li key={`${movement.bookingId}-${movement.petId}`}>
                            <MovementRow movement={movement} />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function MovementRow({ movement }: { movement: PetMovement }) {
    const t = useTranslations();
    const { routes } = useNavigation();

    const status = statusColor(movement.status);
    const illustrated =
        movement.animalTypeCode !== null && isIllustratedType(movement.animalTypeCode);

    const subtitle = movement.animalTypeName
        ? `${movement.animalTypeName} · ${movement.customerName}`
        : movement.customerName;

    return (
        <Link
            href={routes.BookingDetail({ id: movement.bookingId })}
            className="flex items-center gap-3 rounded-2xl border p-2.5 hover:bg-muted/40 hover:border-border transition-colors"
        >
            <span className="flex items-center justify-center size-11 rounded-xl bg-muted shrink-0">
                {illustrated ? (
                    <PetTypeIllustration
                        code={movement.animalTypeCode!}
                        name={movement.animalTypeName ?? ""}
                        className="size-6"
                    />
                ) : (
                    <PawPrint className="size-5 text-muted-foreground" />
                )}
            </span>
            <div className="flex flex-col min-w-0 flex-1 gap-0.5">
                <span className="font-medium truncate leading-tight">{movement.petName}</span>
                <span className="text-xs text-muted-foreground truncate">{subtitle}</span>
            </div>
            <Badge variant="outline" className={cn("shrink-0", status.badge)}>
                {t(
                    `features.hosting-calendar.status.${movement.status}` as Parameters<
                        typeof t
                    >[0],
                )}
            </Badge>
        </Link>
    );
}
