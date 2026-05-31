"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { Plus, Building2 } from "lucide-react";
import { Buildings } from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentSelectCard } from "@/features/establishments/components/establishment-select-card";
import PageLayout from "@/components/layouts/page-layout";

export default function SelectEstablishmentPage() {
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
        <PageLayout
            Icon={Buildings}
            title={t("features.establishments.title")}
            headerTop={
                <Button asChild className="rounded-4xl gap-2">
                    <Link href={routes.BecomeHost()}>
                        <Plus className="size-4" />
                        {t("features.establishments.addNew")}
                    </Link>
                </Button>
            }
        >
            {establishments.length === 0 ? (
                <div className="rounded-2xl border border-dashed">
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <div className="flex items-center justify-center size-16 rounded-full bg-muted mb-4">
                            <Building2 className="size-8 text-muted-foreground" />
                        </div>
                        <h3 className="text-lg font-semibold mb-2">
                            {t("features.establishments.empty.title")}
                        </h3>
                        <p className="text-sm text-muted-foreground mb-6 max-w-sm">
                            {t("features.establishments.empty.description")}
                        </p>
                        <Button asChild className="rounded-4xl gap-2">
                            <Link href={routes.BecomeHost()}>
                                <Plus className="size-4" />
                                {t("features.establishments.addNew")}
                            </Link>
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {establishments.map((establishment) => (
                        <EstablishmentSelectCard
                            key={establishment.id}
                            establishment={establishment}
                        />
                    ))}
                </div>
            )}
        </PageLayout>
    );
}
