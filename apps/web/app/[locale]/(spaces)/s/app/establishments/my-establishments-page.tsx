"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { Building2, MapPin, Phone, Plus, ChevronRight } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { EstablishmentModel } from "@workspace/modules/establishments";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";

function EstablishmentCard({
    establishment,
    href,
}: {
    establishment: EstablishmentModel;
    href: string;
}) {
    const t = useTranslations();

    return (
        <Link href={href} className="block group">
            <div className="rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:border-primary/20 hover:-translate-y-0.5">
                <div className="flex items-start gap-4">
                    <div className="flex items-center justify-center size-12 rounded-2xl bg-primary/10 shrink-0">
                        <Building2 className="size-6 text-primary" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                            <h3 className="font-semibold text-lg truncate">{establishment.name}</h3>
                            <Badge
                                variant={establishment.isActive ? "default" : "secondary"}
                                className="shrink-0"
                            >
                                {establishment.isActive
                                    ? t("features.my-establishments.status.active")
                                    : t("features.my-establishments.status.inactive")}
                            </Badge>
                        </div>
                        {establishment.description && (
                            <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                                {establishment.description}
                            </p>
                        )}
                        <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            {establishment.address && (
                                <div className="flex items-center gap-1.5">
                                    <MapPin className="size-3.5 shrink-0" />
                                    <span>
                                        {establishment.address.city},{" "}
                                        {establishment.address.country}
                                    </span>
                                </div>
                            )}
                            {establishment.phone && (
                                <div className="flex items-center gap-1.5">
                                    <Phone className="size-3.5 shrink-0" />
                                    <span>{establishment.phone}</span>
                                </div>
                            )}
                        </div>
                    </div>
                    <ChevronRight className="size-5 text-muted-foreground shrink-0 self-center opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
            </div>
        </Link>
    );
}

export default function MyEstablishmentsPage() {
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
        <div className="container mx-auto px-4 py-8 max-w-4xl">
            <div className="flex items-center justify-between mb-8">
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
                <div className="grid gap-4">
                    {establishments.map((establishment) => (
                        <EstablishmentCard
                            key={establishment.id}
                            establishment={establishment}
                            href={routes.EstablishmentDetail({ id: establishment.id })}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
