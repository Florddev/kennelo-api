"use client";

import { useTranslations } from "next-intl";

import { SubscriptionStatus } from "@workspace/modules/subscriptions";
import { Badge } from "@workspace/ui/components/badge";

export function SubscriptionStatusBadge({ status }: { status: SubscriptionStatus | null }) {
    const t = useTranslations("features.subscriptions");

    if (status === null) {
        return <Badge variant="outline">{t("status.free")}</Badge>;
    }

    let variant: "default" | "secondary" | "destructive" | "outline" = "outline";

    if (status === "active" || status === "trialing") variant = "default";
    if (status === "past_due" || status === "unpaid") variant = "destructive";
    if (status === "canceled") variant = "secondary";

    return (
        <Badge variant={variant}>{t(`status.${status === "past_due" ? "pastDue" : status}`)}</Badge>
    );
}
