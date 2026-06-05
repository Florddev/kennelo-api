"use client";

import { useMemo, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useQuery } from "@tanstack/react-query";
import { CalendarClock } from "lucide-react";
import { format } from "date-fns";

import { Badge } from "@workspace/ui/components/badge";
import { Input } from "@workspace/ui/components/input";
import { cn } from "@workspace/ui/lib/utils";
import { getAvailabilities, type AvailabilityModel } from "@workspace/modules/establishments";

import { EstablishmentDataTable, type DataTableColumn } from "./establishment-data-table";
import { EstablishmentPageHeader } from "./establishment-page-header";

export function EstablishmentAvailabilitiesList({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const locale = useLocale();
    const [search, setSearch] = useState("");

    const month = useMemo(() => format(new Date(), "yyyy-MM"), []);

    const { data, isLoading } = useQuery({
        queryKey: ["establishment-availabilities", establishmentId, month],
        queryFn: () => getAvailabilities(establishmentId, month),
        staleTime: 60_000,
        enabled: Boolean(establishmentId),
    });

    const dateFormatter = useMemo(
        () => new Intl.DateTimeFormat(locale, { weekday: "short", day: "numeric", month: "short" }),
        [locale],
    );

    const statusLabel = (status: AvailabilityModel["status"]) =>
        status === "open"
            ? t("features.establishments.availabilities.open")
            : t("features.establishments.availabilities.closed");

    const columns: DataTableColumn<AvailabilityModel>[] = [
        {
            key: "date",
            header: t("features.establishments.manager.availabilities.columns.date"),
            cellClassName: "font-medium",
            cell: (availability) => dateFormatter.format(new Date(availability.date)),
        },
        {
            key: "status",
            header: t("common.fields.status"),
            cell: (availability) => (
                <Badge
                    variant="outline"
                    className={cn(
                        availability.status === "open"
                            ? "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40"
                            : "bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/40",
                    )}
                >
                    {statusLabel(availability.status)}
                </Badge>
            ),
        },
        {
            key: "note",
            header: t("common.fields.note"),
            cellClassName: "text-muted-foreground",
            cell: (availability) => availability.note ?? "—",
        },
    ];

    return (
        <div className="flex flex-col gap-6">
            <EstablishmentPageHeader>
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={t("features.establishments.manager.availabilities.filter")}
                    className="w-64"
                />
            </EstablishmentPageHeader>
            <EstablishmentDataTable
                data={data ?? []}
                columns={columns}
                isLoading={isLoading}
                getRowKey={(availability) => String(availability.id)}
                search={search}
                filterRow={(availability, query) =>
                    (availability.note?.toLowerCase().includes(query) ?? false) ||
                    availability.date.toLowerCase().includes(query) ||
                    statusLabel(availability.status).toLowerCase().includes(query)
                }
                emptyIcon={CalendarClock}
                emptyLabel={t("features.establishments.availabilities.empty")}
                renderCount={(count) =>
                    t("features.establishments.manager.availabilities.count", { count })
                }
            />
        </div>
    );
}
