"use client";

import { useMemo } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useQuery } from "@tanstack/react-query";
import { CalendarDays } from "lucide-react";

import { formatAmount } from "@workspace/common";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import { getEstablishmentBookings, type BookingModel } from "@workspace/modules/bookings";

import { UserAvatar } from "@/features/auth/components/user-avatar";
import { statusColor } from "@/features/bookings/lib/booking-colors";
import { EstablishmentDataTable, type DataTableColumn } from "./establishment-data-table";

export function EstablishmentBookingsTable({ establishmentId }: { establishmentId: string }) {
    const t = useTranslations();
    const locale = useLocale();

    const { data, isLoading } = useQuery({
        queryKey: ["establishment-bookings-all", establishmentId],
        queryFn: () => getEstablishmentBookings(establishmentId, { perPage: 100 }),
        staleTime: 60_000,
        enabled: Boolean(establishmentId),
    });

    const dateFormatter = useMemo(
        () => new Intl.DateTimeFormat(locale, { day: "numeric", month: "short", year: "numeric" }),
        [locale],
    );
    const formatDate = (value: string) => dateFormatter.format(new Date(value));

    const columns: DataTableColumn<BookingModel>[] = [
        {
            key: "customer",
            header: t("features.my-establishments.manager.bookings.columns.customer"),
            cell: (booking) => (
                <div className="flex items-center gap-3 min-w-0">
                    <UserAvatar user={booking.user ?? undefined} className="size-8" size="sm" />
                    <div className="flex flex-col min-w-0">
                        <span className="font-medium truncate">
                            {booking.user?.getFullName() ?? "—"}
                        </span>
                        <span className="text-xs text-muted-foreground truncate">
                            {booking.user?.email}
                        </span>
                    </div>
                </div>
            ),
        },
        {
            key: "checkIn",
            header: t("features.my-establishments.manager.bookings.columns.checkIn"),
            cell: (booking) => formatDate(booking.checkInDate),
        },
        {
            key: "checkOut",
            header: t("features.my-establishments.manager.bookings.columns.checkOut"),
            cell: (booking) => formatDate(booking.checkOutDate),
        },
        {
            key: "pets",
            header: t("features.my-establishments.manager.bookings.columns.pets"),
            cell: (booking) => (
                <Badge variant="secondary" className="tabular-nums">
                    {booking.pets?.length ?? 0}
                </Badge>
            ),
        },
        {
            key: "status",
            header: t("features.my-establishments.manager.bookings.columns.status"),
            cell: (booking) => (
                <Badge
                    variant="outline"
                    className={cn("shrink-0", statusColor(booking.status).badge)}
                >
                    {t(
                        `features.hosting-calendar.status.${booking.status}` as Parameters<
                            typeof t
                        >[0],
                    )}
                </Badge>
            ),
        },
        {
            key: "total",
            header: t("features.my-establishments.manager.bookings.columns.total"),
            headClassName: "text-end",
            cellClassName: "text-end font-semibold tabular-nums",
            cell: (booking) => `${formatAmount(Number(booking.totalPrice))} €`,
        },
    ];

    return (
        <EstablishmentDataTable
            data={data ?? []}
            columns={columns}
            isLoading={isLoading}
            getRowKey={(booking) => booking.id}
            searchPlaceholder={t("features.my-establishments.manager.bookings.filter")}
            filterRow={(booking, query) => {
                const name = booking.user?.getFullName().toLowerCase() ?? "";
                const email = booking.user?.email.toLowerCase() ?? "";
                return name.includes(query) || email.includes(query);
            }}
            emptyIcon={CalendarDays}
            emptyLabel={t("features.my-establishments.manager.bookings.empty")}
            renderCount={(count) =>
                t("features.my-establishments.manager.bookings.count", { count })
            }
        />
    );
}
