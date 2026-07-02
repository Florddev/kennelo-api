"use client";

import { useTranslations, useLocale } from "next-intl";

import { Badge } from "@workspace/ui/components/badge";

import { useBookingOperations } from "../hooks/use-booking-operations";

export function BookingOperationsTimeline({
    activityId,
    bookingId,
}: {
    activityId: string;
    bookingId: string;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { operations, isLoading } = useBookingOperations(activityId, bookingId);

    if (isLoading) {
        return null;
    }

    const formatDate = (dateStr: string) =>
        new Date(dateStr).toLocaleDateString(locale, {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });

    return (
        <div className="flex flex-col gap-2">
            <h2 className="text-xl font-semibold">
                {t("features.bookings.detail.operations.title")}
            </h2>

            {operations.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t("features.bookings.detail.operations.empty")}
                </p>
            ) : (
                <div className="bg-muted/50 rounded-2xl p-4 flex flex-col gap-3">
                    {operations.map((operation) => (
                        <div key={operation.id} className="flex items-center justify-between gap-3">
                            <div className="flex flex-col gap-0.5">
                                <Badge variant="outline" className="w-fit">
                                    {t(
                                        `features.bookings.detail.operations.types.${operation.type}`,
                                    )}
                                </Badge>
                                <span className="text-xs text-muted-foreground">
                                    {formatDate(operation.createdAt)}
                                </span>
                            </div>
                            {operation.amount && (
                                <span className="text-sm font-medium">{operation.amount} €</span>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
