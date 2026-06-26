"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { CalendarDays, Globe, Mail, Phone } from "lucide-react";
import { CrownStar, UsersGroupRounded } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import type { ActivityModel } from "@workspace/modules/activities";

const DESCRIPTION_MAX = 220;
const SECONDARY_BUTTON_CLASS =
    "h-10 w-full rounded-md bg-zinc-100 text-sm font-medium text-zinc-900 hover:bg-zinc-200";

type HostAboutSectionProps = {
    activity: ActivityModel;
};

function extractYear(value: string | null): string | null {
    if (!value) return null;
    const match = value.match(/\d{4}/);
    return match ? match[0] : null;
}

function normalizeWebsiteUrl(website: string): string {
    return /^https?:\/\//i.test(website) ? website : `https://${website}`;
}

function websiteLabel(website: string): string {
    return website.replace(/^https?:\/\//i, "").replace(/\/$/, "");
}

function ProBadge({ isProfessional }: { isProfessional: boolean }) {
    const t = useTranslations();
    return (
        <span
            className={cn(
                "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold",
                isProfessional ? "bg-foreground/90 text-background" : "bg-zinc-100 text-foreground",
            )}
        >
            {isProfessional ? (
                <CrownStar weight="Bold" className="size-3 shrink-0 text-secondary" />
            ) : (
                <UsersGroupRounded weight="Bold" className="size-3 shrink-0" />
            )}
            {isProfessional
                ? t("features.explore.card.pro")
                : t("features.explore.card.individual")}
        </span>
    );
}

function InfoRow({ icon, children }: { icon: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2 text-sm text-foreground">
            <span className="text-muted-foreground">{icon}</span>
            {children}
        </div>
    );
}

function Description({ description }: { description: string | null }) {
    const t = useTranslations();
    const [expanded, setExpanded] = useState(false);

    if (!description) {
        return null;
    }

    const shouldTruncate = description.length > DESCRIPTION_MAX;
    const truncated = shouldTruncate ? `${description.slice(0, DESCRIPTION_MAX)}...` : description;
    const textToDisplay = expanded ? description : truncated;

    return (
        <div className="flex flex-col items-center gap-4">
            <p className="whitespace-pre-wrap text-sm text-foreground">{textToDisplay}</p>
            {shouldTruncate && (
                <Button
                    variant="secondary"
                    className={SECONDARY_BUTTON_CLASS}
                    onClick={() => setExpanded((value) => !value)}
                >
                    {expanded
                        ? t("features.host.detail.readLess")
                        : t("features.host.detail.readMore")}
                </Button>
            )}
        </div>
    );
}

export function HostAboutSection({ activity }: HostAboutSectionProps) {
    const t = useTranslations();
    const memberSinceYear = extractYear(activity.manager?.createdAt ?? activity.createdAt);
    const hasContacts = Boolean(
        memberSinceYear || activity.website || activity.phone || activity.email,
    );

    return (
        <section data-slot="host-about-section" className="flex flex-col gap-4 px-1 pt-4">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.about")}
            </h2>

            <div className="flex flex-wrap items-center gap-2">
                <ProBadge isProfessional={activity.isProfessional} />
                {activity.type && (
                    <span className="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-foreground">
                        {t(`features.activities.types.${activity.type}`)}
                    </span>
                )}
            </div>

            <Description description={activity.description} />

            {hasContacts && (
                <div className="flex flex-col gap-2.5">
                    {memberSinceYear && (
                        <InfoRow icon={<CalendarDays className="size-4" />}>
                            {t("features.host.detail.memberSince", { year: memberSinceYear })}
                        </InfoRow>
                    )}
                    {activity.website && (
                        <InfoRow icon={<Globe className="size-4" />}>
                            <a
                                href={normalizeWebsiteUrl(activity.website)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-primary underline underline-offset-2"
                            >
                                {websiteLabel(activity.website)}
                            </a>
                        </InfoRow>
                    )}
                    {activity.phone && (
                        <InfoRow icon={<Phone className="size-4" />}>
                            <a href={`tel:${activity.phone}`} className="hover:underline" dir="ltr">
                                {activity.phone}
                            </a>
                        </InfoRow>
                    )}
                    {activity.email && (
                        <InfoRow icon={<Mail className="size-4" />}>
                            <a
                                href={`mailto:${activity.email}`}
                                className="hover:underline"
                                dir="ltr"
                            >
                                {activity.email}
                            </a>
                        </InfoRow>
                    )}
                </div>
            )}
        </section>
    );
}
