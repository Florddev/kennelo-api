"use client";

import { ActivityPageHeader } from "@/features/activities/components/activity-page-header";

export default function ActivitySettingsInformations() {
    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader />
            <span>Informations</span>
        </div>
    );
}
