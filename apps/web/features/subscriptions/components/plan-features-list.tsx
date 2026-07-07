"use client";

import { useTranslations } from "next-intl";
import { Check } from "lucide-react";

import { SubscriptionPlanModel } from "@workspace/modules/subscriptions";

function formatLimit(value: number | null, unlimitedLabel: string): string {
    if (value === null || value < 0) {
        return unlimitedLabel;
    }

    return String(value);
}

export function PlanFeaturesList({ plan }: { plan: SubscriptionPlanModel }) {
    const t = useTranslations("features.subscriptions");
    const unlimited = t("features.unlimited");

    const items = [
        t("features.commission", { value: plan.commissionPercent() }),
        t("features.activities", { value: formatLimit(plan.limit("max_activities"), unlimited) }),
        t("features.cycles", {
            value: formatLimit(plan.limit("max_cycles_per_activity"), unlimited),
        }),
        t("features.photos", { value: formatLimit(plan.limit("max_photos"), unlimited) }),
    ];

    return (
        <ul className="flex flex-col gap-2">
            {items.map((item) => (
                <li key={item} className="flex items-center gap-2 text-sm">
                    <Check className="size-4 shrink-0 text-primary" />
                    <span>{item}</span>
                </li>
            ))}
        </ul>
    );
}
