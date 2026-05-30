"use client";

import { Building2, MapPin } from "lucide-react";
import { useTranslations } from "next-intl";
import Link from "next/link";

import type { EstablishmentModel } from "@workspace/modules/establishments";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import Image from "next/image";
import { useEstablishmentCapacities } from "../hooks/use-establishment-capacities";
import { EstablishmentSpeciesAvatars } from "./establishment-species-avatars";

const STATUS_BADGE_CLASS = {
    active: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40",
    inactive: "bg-zinc-500/15 text-zinc-700 dark:text-zinc-300 border-zinc-500/40",
} as const;

type EstablishmentSelectCardProps = {
    establishment: EstablishmentModel;
};

export function EstablishmentSelectCard({ establishment }: EstablishmentSelectCardProps) {
    const t = useTranslations();
    const { routes } = useNavigation();
    const { capacities } = useEstablishmentCapacities(establishment.id);

    const avatarUrl = establishment.getAvatarUrl();
    const detailHref = routes.EstablishmentDetail({ id: establishment.id });
    const statusKey = establishment.isActive ? "active" : "inactive";

    return (
        <Link
            href={detailHref}
            data-slot="establishment-select-card"
            className="group flex flex-col rounded-2xl border bg-card overflow-hidden transition-all hover:shadow-lg hover:border-primary/30 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <div className="relative h-40 w-full overflow-hidden bg-gradient-to-br from-primary/20 via-primary/5 to-transparent">
                {avatarUrl ? (
                    <Image
                        src={avatarUrl}
                        alt={establishment.name}
                        className="size-full object-cover"
                        fill
                    />
                ) : (
                    <div className="flex size-full items-center justify-center">
                        <Building2 className="size-10 text-primary" />
                    </div>
                )}
            </div>

            <div className="flex flex-col gap-4 px-5 pt-5 pb-5 flex-1">
                <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="font-semibold text-lg truncate">{establishment.name}</h3>
                        <Badge
                            variant="outline"
                            className={cn("shrink-0", STATUS_BADGE_CLASS[statusKey])}
                        >
                            {t(`features.my-establishments.status.${statusKey}`)}
                        </Badge>
                    </div>
                    {establishment.address && (
                        <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <MapPin className="size-3.5 shrink-0" />
                            <span className="truncate">
                                {establishment.address.city}, {establishment.address.country}
                            </span>
                        </div>
                    )}
                </div>

                <div className="flex flex-col gap-2">
                    <span className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                        {t("features.my-establishments.card.species")}
                    </span>
                    <EstablishmentSpeciesAvatars capacities={capacities} />
                </div>
            </div>
        </Link>
    );
}
