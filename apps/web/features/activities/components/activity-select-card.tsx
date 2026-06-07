"use client";

import { Building2, MapPin } from "lucide-react";
import { useTranslations } from "next-intl";
import Link from "next/link";

import type { ActivityModel } from "@workspace/modules/activities";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import Image from "next/image";
import { useActivityCycleSettings } from "../hooks/use-activity-cycle-settings";
import { ActivitySpeciesAvatars } from "./activity-species-avatars";

const STATUS_BADGE_CLASS = {
    active: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40",
    inactive: "bg-zinc-500/15 text-zinc-700 dark:text-zinc-300 border-zinc-500/40",
} as const;

type ActivitySelectCardProps = {
    activity: ActivityModel;
};

export function ActivitySelectCard({ activity }: ActivitySelectCardProps) {
    const t = useTranslations();
    const { routes } = useNavigation();
    const { settings } = useActivityCycleSettings(activity.id);

    const avatarUrl = activity.getAvatarUrl();
    const detailHref = routes.ActivityDetails({ id: activity.id });
    const statusKey = activity.isActive ? "active" : "inactive";

    return (
        <Link
            href={detailHref}
            data-slot="activity-select-card"
            className="group flex flex-col rounded-2xl border bg-card overflow-hidden transition-all hover:shadow-lg hover:border-primary/30 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <div className="relative h-40 w-full overflow-hidden bg-gradient-to-br from-primary/20 via-primary/5 to-transparent">
                {avatarUrl ? (
                    <Image
                        src={avatarUrl}
                        alt={activity.name}
                        className="size-full object-cover"
                        fill
                    />
                ) : (
                    <div className="flex size-full items-center justify-center">
                        <Building2 className="size-10 text-primary" />
                    </div>
                )}
            </div>

            <div className="flex flex-col gap-4 px-5 pt-5 pb-5 flex-1">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="font-semibold text-lg truncate">{activity.name}</h3>
                        <Badge
                            variant="outline"
                            className={cn("shrink-0", STATUS_BADGE_CLASS[statusKey])}
                        >
                            {t(`features.activities.status.${statusKey}`)}
                        </Badge>
                    </div>
                    {activity.address && (
                        <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <MapPin className="size-3.5 shrink-0" />
                            <span className="truncate">
                                {activity.address.city}, {activity.address.country}
                            </span>
                        </div>
                    )}
                </div>

                <div className="flex flex-col gap-2">
                    <span className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                        {t("features.activities.card.species")}
                    </span>
                    <ActivitySpeciesAvatars settings={settings} />
                </div>
            </div>
        </Link>
    );
}
