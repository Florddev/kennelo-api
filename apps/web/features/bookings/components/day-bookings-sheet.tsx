"use client";

import Link from "next/link";
import { useLocale, useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";

import { formatAmount } from "@workspace/common";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@workspace/ui/components/sheet";
import { cn } from "@workspace/ui/lib/utils";
import {
    cancelActivityBooking,
    completeActivityBooking,
    confirmActivityBooking,
    type BookingModel,
} from "@workspace/modules/bookings";

import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useAsyncState } from "@/hooks/use-async-state";
import { useNavigation } from "@/hooks/use-navigation";
import { activityColor, statusColor } from "../lib/booking-colors";

type ActivityMeta = {
    id: string;
    name: string;
    colorIndex: number;
};

type DayBookingsSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    selectedDate: Date | null;
    bookings: BookingModel[];
    activityMetaById: Record<string, ActivityMeta>;
};

export function DayBookingsSheet({
    open,
    onOpenChange,
    selectedDate,
    bookings,
    activityMetaById,
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

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full sm:max-w-md flex flex-col gap-0 p-0">
                <SheetHeader className="border-b">
                    <SheetTitle className="capitalize">
                        {t("features.hosting-calendar.sheet.title", { date: dateLabel })}
                    </SheetTitle>
                    <SheetDescription>
                        {t("features.hosting-calendar.sheet.bookingCount", {
                            count: bookings.length,
                        })}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-6 flex flex-col gap-4">
                    {bookings.length === 0 ? (
                        <p className="text-sm text-muted-foreground text-center py-8">
                            {t("features.hosting-calendar.sheet.empty")}
                        </p>
                    ) : (
                        bookings.map((booking) => (
                            <BookingCard
                                key={booking.id}
                                booking={booking}
                                activityMeta={activityMetaById[booking.activityId]}
                            />
                        ))
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

function BookingCard({
    booking,
    activityMeta,
}: {
    booking: BookingModel;
    activityMeta?: ActivityMeta;
}) {
    const locale = useLocale();
    const t = useTranslations();
    const { routes } = useNavigation();

    const status = statusColor(booking.status);
    const color = activityColor(activityMeta?.colorIndex ?? 0);

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
    const checkInLabel = dateFormatter.format(new Date(booking.checkInDate));
    const checkOutLabel = dateFormatter.format(new Date(booking.checkOutDate));

    const customer = booking.user;
    const customerName = customer ? customer.getFullName() : "—";
    const showActions =
        booking.isPending() || booking.isConfirmed() || booking.status === "in_progress";

    return (
        <article
            data-slot="booking-card"
            className={cn(
                "rounded-2xl border bg-card flex flex-col overflow-hidden",
                color.chipBorder,
            )}
        >
            <header className="flex items-center justify-between gap-3 px-4 py-3 border-b">
                <div className="flex items-center gap-3 min-w-0">
                    <UserAvatar user={customer ?? undefined} className="size-10" />
                    <div className="flex flex-col min-w-0">
                        <span className="font-medium truncate">{customerName}</span>
                        {activityMeta && (
                            <span
                                className={cn(
                                    "text-xs font-medium px-1.5 py-0.5 rounded-md w-fit",
                                    color.chipBg,
                                    color.chipText,
                                )}
                            >
                                {activityMeta.name}
                            </span>
                        )}
                    </div>
                </div>
                <Badge variant="outline" className={cn("shrink-0", status.badge)}>
                    {t(
                        `features.hosting-calendar.status.${booking.status}` as Parameters<
                            typeof t
                        >[0],
                    )}
                </Badge>
            </header>

            <div className="px-4 py-3 grid grid-cols-2 gap-3 text-sm">
                <div className="flex flex-col">
                    <span className="text-xs text-muted-foreground">
                        {t("features.hosting-calendar.sheet.checkIn")}
                    </span>
                    <span className="font-medium">{checkInLabel}</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-xs text-muted-foreground">
                        {t("features.hosting-calendar.sheet.checkOut")}
                    </span>
                    <span className="font-medium">{checkOutLabel}</span>
                </div>
            </div>

            {booking.pets && booking.pets.length > 0 && (
                <>
                    <Separator />
                    <div className="px-4 py-3 flex flex-col gap-1.5">
                        <span className="text-xs text-muted-foreground">
                            {t("features.hosting-calendar.sheet.pets")}
                        </span>
                        <ul className="flex flex-wrap gap-1.5">
                            {booking.pets.map((pet) => (
                                <li key={pet.id}>
                                    <Badge variant="flat" size="sm">
                                        {pet.name}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    </div>
                </>
            )}

            <Separator />
            <div className="px-4 py-3 flex items-center justify-between">
                <span className="text-sm text-muted-foreground">
                    {t("features.hosting-calendar.sheet.total")}
                </span>
                <span className="text-base font-semibold">
                    {formatAmount(Number(booking.totalPrice))} €
                </span>
            </div>

            {showActions && (
                <>
                    <Separator />
                    <BookingCardActions booking={booking} />
                </>
            )}

            <Separator />
            <div className="px-4 py-2">
                <Button size="sm" variant="ghost" className="text-muted-foreground -ms-2" asChild>
                    <Link href={routes.BookingDetail({ id: booking.id })}>
                        {t("common.actions.viewDetails")}
                    </Link>
                </Button>
            </div>
        </article>
    );
}

function BookingCardActions({ booking }: { booking: BookingModel }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute: runConfirm, isLoading: isConfirming } = useAsyncState();
    const { execute: runCancel, isLoading: isCancelling } = useAsyncState();
    const { execute: runComplete, isLoading: isCompleting } = useAsyncState();

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ["hosting-calendar-bookings"] });
    };

    const onConfirm = () =>
        runConfirm(() => confirmActivityBooking(booking.activityId, booking.id), {
            onSuccess: invalidate,
        });

    const onCancel = () =>
        runCancel(() => cancelActivityBooking(booking.activityId, booking.id), {
            onSuccess: invalidate,
        });

    const onComplete = () =>
        runComplete(() => completeActivityBooking(booking.activityId, booking.id), {
            onSuccess: invalidate,
        });

    return (
        <footer className="px-4 py-3 flex flex-wrap items-center gap-2">
            {booking.isPending() && (
                <Button
                    size="sm"
                    onClick={onConfirm}
                    disabled={isConfirming}
                    className="rounded-full"
                >
                    {t("features.hosting-calendar.sheet.actions.confirm")}
                </Button>
            )}
            {(booking.isConfirmed() || booking.status === "in_progress") && (
                <Button
                    size="sm"
                    variant="outline"
                    onClick={onComplete}
                    disabled={isCompleting}
                    className="rounded-full"
                >
                    {t("features.hosting-calendar.sheet.actions.complete")}
                </Button>
            )}
            <Button
                size="sm"
                variant="ghost"
                onClick={onCancel}
                disabled={isCancelling}
                className="rounded-full text-destructive hover:text-destructive ms-auto"
            >
                {t("features.hosting-calendar.sheet.actions.cancel")}
            </Button>
        </footer>
    );
}
