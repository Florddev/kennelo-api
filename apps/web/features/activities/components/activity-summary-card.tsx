import { useTranslations } from "next-intl";
import { BadgeCheck, Building2, MapPin } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import type { ActivityModel } from "@workspace/modules/activities";

type ActivitySummaryCardProps = {
    activity: ActivityModel;
};

export function ActivitySummaryCard({ activity }: ActivitySummaryCardProps) {
    const t = useTranslations();
    const imageUrl = activity.getAvatarUrl();
    const manager = activity.manager;

    return (
        <div data-slot="activity-summary-card" className="flex items-center gap-4">
            <div className="relative shrink-0">
                <Avatar className="size-20 rounded-3xl overflow-hidden">
                    {imageUrl && (
                        <AvatarImage
                            src={imageUrl}
                            alt={activity.name}
                            className="object-cover rounded-none"
                        />
                    )}
                    <AvatarFallback className="rounded-3xl bg-muted">
                        <Building2 className="size-8 text-muted-foreground" />
                    </AvatarFallback>
                </Avatar>
                {manager && (
                    <Avatar className="bg-background absolute -bottom-2 -end-2 size-9 border-2 border-background shadow-sm">
                        {manager.avatarUrl && (
                            <AvatarImage src={manager.avatarUrl} alt={manager.getFullName()} />
                        )}
                        <AvatarFallback className="text-[10px]">
                            {manager.getInitials()}
                        </AvatarFallback>
                    </Avatar>
                )}
            </div>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <p className="truncate text-base font-semibold text-slate-900">{activity.name}</p>
                {manager && (
                    <span className="flex items-center gap-1 truncate text-xs text-muted-foreground">
                        {t("features.host.detail.host", { name: manager.firstName })}
                        {manager.isIdVerified && (
                            <BadgeCheck className="size-3.5 shrink-0 text-emerald-600" />
                        )}
                    </span>
                )}
                {activity.address && (
                    <span className="bg-muted flex w-fit max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-xs text-muted-foreground">
                        <MapPin className="size-3.5 shrink-0" />
                        <span className="truncate">
                            {activity.address.city}, {activity.address.country}
                        </span>
                    </span>
                )}
            </div>
        </div>
    );
}
