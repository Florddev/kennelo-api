"use client";

import { useTranslations } from "next-intl";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityInfoSection } from "@/features/activities/components/activity-info-section";
import { ActivityPageHeader } from "@/features/activities/components/activity-page-header";

export default function ActivityInfoPage() {
    const t = useTranslations();
    const { params } = useNavigation<{ id: string }>();

    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader />
            <ActivityInfoSection activityId={params.id} t={t} />
        </div>
    );
}
