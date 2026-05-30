"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { Building2, Plus } from "lucide-react";

import { Button } from "@workspace/ui/components/button";

import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentSelectCard } from "@/features/establishments/components/establishment-select-card";

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
        <div className="px-6 py-6">
            <div className="flex flex-wrap items-end justify-between gap-4 mb-8">
                <div className="flex flex-col gap-1">
                    <h1 className="text-3xl font-bold tracking-tight">
                        {t("features.my-establishments.title")}
                    </h1>
                    <p className="text-muted-foreground">
                        {t("features.my-establishments.description")}
                    </p>
                </div>
                <Button asChild className="rounded-4xl gap-2">
                    <Link href={routes.BecomeHost()}>
                        <Plus className="size-4" />
                        {t("features.my-establishments.addNew")}
                    </Link>
                </Button>
            </div>

            {establishments.length === 0 ? (
                <div className="rounded-2xl border border-dashed">
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <div className="flex items-center justify-center size-16 rounded-full bg-muted mb-4">
                            <Building2 className="size-8 text-muted-foreground" />
                        </div>
                        <h3 className="text-lg font-semibold mb-2">
                            {t("features.my-establishments.empty.title")}
                        </h3>
                        <p className="text-sm text-muted-foreground mb-6 max-w-sm">
                            {t("features.my-establishments.empty.description")}
                        </p>
                        <Button asChild className="rounded-4xl gap-2">
                            <Link href={routes.BecomeHost()}>
                                <Plus className="size-4" />
                                {t("features.my-establishments.addNew")}
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
        </div>
    );
}
