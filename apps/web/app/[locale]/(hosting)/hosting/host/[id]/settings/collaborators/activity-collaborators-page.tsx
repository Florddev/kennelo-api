"use client";

import { useTranslations } from "next-intl";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";

import { useNavigation } from "@/hooks/use-navigation";
import { ActivityCollaboratorsManager } from "@/features/activities/components/activity-collaborators-manager";
import { ActivityRolesPanel } from "@/features/activities/components/activity-roles-panel";

export default function ActivityCollaboratorsPage() {
    const { params } = useNavigation<{ id: string }>();
    const t = useTranslations();

    return (
        <Tabs defaultValue="collaborators" className="flex flex-col gap-6">
            <TabsList>
                <TabsTrigger value="collaborators">
                    {t("features.activities.manager.collaborators.title")}
                </TabsTrigger>
                <TabsTrigger value="roles">
                    {t("features.activities.manager.roles.title")}
                </TabsTrigger>
            </TabsList>
            <TabsContent value="collaborators">
                <ActivityCollaboratorsManager activityId={params.id} />
            </TabsContent>
            <TabsContent value="roles">
                <ActivityRolesPanel activityId={params.id} />
            </TabsContent>
        </Tabs>
    );
}
