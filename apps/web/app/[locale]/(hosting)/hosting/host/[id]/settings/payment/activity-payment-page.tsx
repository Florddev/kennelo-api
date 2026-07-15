"use client";

import { useTranslations } from "next-intl";
import { CreditCard, Inbox } from "lucide-react";

import { Tabs, TabsList, TabsTrigger, TabsContent } from "@workspace/ui/components/tabs";

import { useAuth } from "@/features/auth";
import { ActivityPageHeader } from "@/features/activities/components/activity-page-header";
import { EmbeddedConnectOnboarding } from "@/features/activities/components/embedded-connect-onboarding";
import { ReservationReviewList } from "@/features/bookings/components/reservation-review-list";

export default function ActivityPaymentPage() {
    const t = useTranslations();
    const { activities } = useAuth();

    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader />
            <Tabs defaultValue="bank" orientation="horizontal" className="flex flex-col">
                <TabsList variant="line" className="border-b w-full justify-start gap-0">
                    <TabsTrigger value="bank" className="gap-2 px-4 py-2.5">
                        <CreditCard className="size-4" />
                        {t("features.activities.manager.payment.tabs.bankAccount")}
                    </TabsTrigger>
                    <TabsTrigger value="pending" className="gap-2 px-4 py-2.5">
                        <Inbox className="size-4" />
                        {t("features.activities.manager.payment.tabs.pendingRequests")}
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="bank" className="pt-6">
                    <p className="text-xs text-muted-foreground mb-3">
                        {t("features.activities.manager.payment.bankAccountShared")}
                    </p>
                    <EmbeddedConnectOnboarding />
                </TabsContent>
                <TabsContent value="pending" className="pt-6 flex flex-col gap-6">
                    {activities.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-8">
                            {t("features.activities.manager.payment.noActivities")}
                        </p>
                    )}
                    {activities.map((activity) => (
                        <section key={activity.id} className="flex flex-col gap-2">
                            <h3 className="text-sm font-semibold text-foreground">
                                {activity.name}
                            </h3>
                            <ReservationReviewList activityId={activity.id} />
                        </section>
                    ))}
                </TabsContent>
            </Tabs>
        </div>
    );
}
