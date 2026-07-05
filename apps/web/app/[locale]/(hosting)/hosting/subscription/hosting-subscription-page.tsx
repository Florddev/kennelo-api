"use client";

import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { CrownLine } from "@solar-icons/react";

import { ActivityModel } from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import PageLayout from "@/components/layouts/page-layout";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { SubscriptionManager } from "@/features/subscriptions";

export default function HostingSubscriptionPage() {
    const t = useTranslations();
    const searchParams = useSearchParams();
    const { isLoaded, isAuthenticated, activities } = useAuth();
    const { routes, router } = useNavigation();

    const requestedActivityId = searchParams.get("activity");
    const checkoutStatus = searchParams.get("checkout");

    const [selectedActivityId, setSelectedActivityId] = useState<string | null>(null);

    useEffect(() => {
        if (isLoaded && !isAuthenticated) {
            router.push(routes.Login());
        }
    }, [isLoaded, isAuthenticated, router, routes]);

    useEffect(() => {
        if (checkoutStatus === "success") {
            toast.success(t("features.subscriptions.checkout.success"));
        } else if (checkoutStatus === "cancel") {
            toast.info(t("features.subscriptions.checkout.canceled"));
        }
    }, [checkoutStatus, t]);

    const selectedActivity = useMemo<ActivityModel | null>(() => {
        if (activities.length === 0) {
            return null;
        }

        const preferredId = selectedActivityId ?? requestedActivityId;
        const preferred = activities.find((activity) => activity.id === preferredId);

        return preferred ?? activities[0] ?? null;
    }, [activities, selectedActivityId, requestedActivityId]);

    if (!isLoaded || !isAuthenticated) {
        return null;
    }

    return (
        <PageLayout Icon={CrownLine} title={t("features.subscriptions.title")}>
            {activities.length === 0 ? (
                <p className="text-muted-foreground">{t("features.subscriptions.noActivity")}</p>
            ) : (
                <div className="flex flex-col gap-6">
                    {activities.length > 1 && (
                        <div className="flex flex-wrap gap-2">
                            {activities.map((activity) => (
                                <Button
                                    key={activity.id}
                                    variant={
                                        selectedActivity?.id === activity.id ? "default" : "outline"
                                    }
                                    className="rounded-4xl"
                                    onClick={() => setSelectedActivityId(activity.id)}
                                >
                                    <span className={cn("truncate max-w-40")}>{activity.name}</span>
                                </Button>
                            ))}
                        </div>
                    )}

                    {selectedActivity && <SubscriptionManager activity={selectedActivity} />}
                </div>
            )}
        </PageLayout>
    );
}
