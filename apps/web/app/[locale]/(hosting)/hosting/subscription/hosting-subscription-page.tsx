"use client";

import { useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { CrownLine } from "@solar-icons/react";

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

    if (!isLoaded || !isAuthenticated) {
        return null;
    }

    return (
        <PageLayout Icon={CrownLine} title={t("features.subscriptions.title")}>
            <SubscriptionManager />
        </PageLayout>
    );
}
