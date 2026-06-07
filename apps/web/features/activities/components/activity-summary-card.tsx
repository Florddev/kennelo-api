import { Building2 } from "lucide-react";
import type { ActivityModel } from "@workspace/modules/activities";

type ActivitySummaryCardProps = {
    activity: ActivityModel;
};

export function ActivitySummaryCard({ activity }: ActivitySummaryCardProps) {
    return (
        <div
            data-slot="activity-summary-card"
            className="flex items-center gap-3 rounded-2xl border p-3"
        >
            <div className="flex size-16 shrink-0 items-center justify-center rounded-xl bg-muted">
                <Building2 className="size-7 text-muted-foreground" />
            </div>
            <div className="flex flex-1 flex-col gap-1 min-w-0">
                <p className="truncate text-sm font-semibold text-slate-900">{activity.name}</p>
                {activity.address && (
                    <p className="truncate text-xs text-muted-foreground">
                        {activity.address.city}, {activity.address.country}
                    </p>
                )}
            </div>
        </div>
    );
}
