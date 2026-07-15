"use client";

import { useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { CrownLine } from "@solar-icons/react";

import posthog from "posthog-js";
import PageLayout from "@/components/layouts/page-layout";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { SubscriptionManager } from "@/features/subscriptions";

export default function HostingSubscriptionPage() {
    const t = useTranslations();
    const searchParams = useSearchParams();
    const { isLoaded, isAuthenticated } = useAuth();
    const { routes, router } = useNavigation();

    const checkoutStatus = searchParams.get("checkout");

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

    useEffect(() => {
        if (isLoaded && isAuthenticated) {
            posthog.capture("subscription_viewed", {
                checkout_status: checkoutStatus ?? undefined,
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isLoaded, isAuthenticated]);

    if (!isLoaded || !isAuthenticated) {
        return null;
    }

    return (
        <PageLayout
            Icon={CrownLine}
            title={t("features.subscriptions.title")}
            containerClassName="p-4 md:p-8"
        >
            <SubscriptionManager />
        </PageLayout>
    );
}
