"use client";

import { BookingStatus } from "@workspace/modules/bookings";
import { Badge } from "@workspace/ui/components/badge";
import { useTranslations } from "next-intl";

export function BookingStatusBadge({ status }: { status: BookingStatus }) {
    const t = useTranslations("features.bookings");
    let variant: "default" | "secondary" | "destructive" | "outline" = "outline";

    if (status === "confirmed") variant = "default";
    if (status === "in_progress") variant = "secondary";
    if (status === "cancelled" || status === "rejected") variant = "destructive";

    return (
        <Badge variant={variant} className="capitalize">
            {t(`status.${status === "in_progress" ? "inProgress" : status}`)}
        </Badge>
    );
}
