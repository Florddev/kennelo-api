"use client";

import { useEffect } from "react";
import { useTranslations } from "next-intl";
import { CreditCard, Inbox } from "lucide-react";

import { Tabs, TabsList, TabsTrigger, TabsContent } from "@workspace/ui/components/tabs";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { EmbeddedConnectOnboarding } from "@/features/establishments/components/embedded-connect-onboarding";
import { ReservationReviewList } from "@/features/bookings/components/reservation-review-list";

export default function TempPage() {
    const t = useTranslations();
    const { isLoaded, isAuthenticated, establishments } = useAuth();
    const { routes, router } = useNavigation();

    useEffect(() => {
        if (isLoaded && !isAuthenticated) {
            router.push(routes.Login());
        }
    }, [isLoaded, isAuthenticated, router, routes]);

    if (!isLoaded || !isAuthenticated) {
        return null;
    }

    return (
        <div className="container mx-auto px-4 py-8 max-w-3xl flex flex-col gap-6">
            <Tabs defaultValue="bank" orientation="horizontal" className="flex flex-col">
                <TabsList variant="line" className="border-b w-full justify-start gap-0">
                    <TabsTrigger value="bank" className="gap-2 px-4 py-2.5">
                        <CreditCard className="size-4" />
                        {t("features.hosting.temp.tabs.bankAccount")}
                    </TabsTrigger>
                    <TabsTrigger value="pending" className="gap-2 px-4 py-2.5">
                        <Inbox className="size-4" />
                        {t("features.hosting.temp.tabs.pendingRequests")}
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="bank" className="pt-6">
                    <p className="text-xs text-muted-foreground mb-3">
                        {t("features.hosting.bankAccountShared")}
                    </p>
                    <EmbeddedConnectOnboarding />
                </TabsContent>
                <TabsContent value="pending" className="pt-6 flex flex-col gap-6">
                    {establishments.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-8">
                            {t("features.hosting.temp.noEstablishments")}
                        </p>
                    )}
                    {establishments.map((establishment) => (
                        <section key={establishment.id} className="flex flex-col gap-2">
                            <h3 className="text-sm font-semibold text-foreground">
                                {establishment.name}
                            </h3>
                            <ReservationReviewList establishmentId={establishment.id} />
                        </section>
                    ))}
                </TabsContent>
            </Tabs>
        </div>
    );
}
