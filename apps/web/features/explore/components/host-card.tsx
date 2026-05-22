"use client";

import Image from "next/image";
import { MapPin, ImageIcon, Star } from "lucide-react";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { useNavigation } from "@/hooks/use-navigation";
import type { EstablishmentModel } from "@workspace/modules/establishments";

type HostCardProps = {
    host: EstablishmentModel;
    variant?: "vertical" | "horizontal";
    highlighted?: boolean;
    className?: string;
    onClick?: () => void;
};

function ProBadge({ isPro }: { isPro: boolean }) {
    const t = useTranslations();
    return (
        <div
            className={cn(
                "flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold backdrop-blur-sm",
                isPro
                    ? "bg-foreground/85 text-background"
                    : "bg-background/85 text-foreground border border-border/40",
            )}
        >
            <span
                className={cn(
                    "size-2 rounded-full shrink-0",
                    isPro ? "bg-secondary" : "bg-muted-foreground",
                )}
            />
            {isPro ? t("features.explore.card.pro") : t("features.explore.card.individual")}
        </div>
    );
}

function PetIcons({ types, max = 3 }: { types: string[]; max?: number }) {
    const t = useTranslations();
    const visible = types.slice(0, max);
    const extra = types.length - max;

    return (
        <div className="flex items-center gap-1">
            {visible.map((type) => (
                <div
                    key={type}
                    title={t(`features.pets.types.${type}`)}
                    className="size-6 rounded-full border border-border bg-muted flex items-center justify-center overflow-hidden"
                >
                    <Image
                        src={`/illustrations/pets/${type}.svg`}
                        alt={t(`features.pets.types.${type}`)}
                        width={16}
                        height={16}
                        className="size-4"
                    />
                </div>
            ))}
            {extra > 0 && (
                <div className="size-6 rounded-full border border-border bg-muted flex items-center justify-center text-[9px] font-bold text-muted-foreground">
                    +{extra}
                </div>
            )}
        </div>
    );
}

function LocationLine({ host }: { host: EstablishmentModel }) {
    const t = useTranslations();
    const typeLabel = host.type
        ? t(`features.establishments.types.${host.type}` as Parameters<typeof t>[0])
        : null;
    const distanceLabel =
        host.distance !== null
            ? t("features.explore.card.distanceKm", { distance: host.distance })
            : null;
    const secondary = distanceLabel ?? host.address?.city ?? null;

    if (!typeLabel && !secondary) return null;

    return (
        <div className="flex items-center gap-1 text-xs text-muted-foreground">
            <MapPin className="size-3 shrink-0" />
            {typeLabel && <span className="shrink-0">{typeLabel}</span>}
            {typeLabel && secondary && <span aria-hidden="true">·</span>}
            {secondary && (
                <span className={distanceLabel ? "shrink-0" : "truncate"}>{secondary}</span>
            )}
        </div>
    );
}

function RatingBadge({ rating, reviewCount }: { rating: number | null; reviewCount: number }) {
    const t = useTranslations();
    if (!rating) return null;
    return (
        <div className="flex items-center gap-1 text-xs text-muted-foreground">
            <Star className="size-3 fill-secondary text-secondary shrink-0" />
            <span className="font-semibold text-foreground">{rating.toFixed(1)}</span>
            <span>({t("features.explore.card.reviews", { count: reviewCount })})</span>
        </div>
    );
}

function HostAvatar({ url, name }: { url: string | undefined; name: string }) {
    if (url) {
        return (
            <Image
                src={url}
                alt={name}
                fill
                className="object-cover"
                sizes="(max-width: 768px) 50vw, 200px"
            />
        );
    }
    return <ImageIcon className="size-7 text-muted-foreground/50" />;
}

export function HostCard({
    host,
    variant = "vertical",
    highlighted = false,
    className,
    onClick,
}: HostCardProps) {
    const t = useTranslations();
    const { routes, router } = useNavigation();
    const avatarUrl = host.getAvatarUrl();

    function handleClick() {
        if (onClick) {
            onClick();
        } else {
            router.push(routes.HostDetail({ id: host.id }));
        }
    }

    if (variant === "horizontal") {
        return (
            <button
                data-slot="host-card-horizontal"
                onClick={handleClick}
                className={cn(
                    "w-full flex items-start gap-3 p-3 rounded-2xl text-start transition-colors",
                    highlighted ? "bg-secondary/8 ring-1 ring-secondary/25" : "hover:bg-muted/50",
                    className,
                )}
            >
                <div className="size-20 rounded-xl bg-muted shrink-0 overflow-hidden flex items-center justify-center relative">
                    <HostAvatar url={avatarUrl} name={host.name} />
                </div>
                <div className="flex-1 min-w-0 flex flex-col gap-1">
                    <div className="flex items-start justify-between gap-2">
                        <span className="font-semibold text-sm leading-tight line-clamp-1">
                            {host.name}
                        </span>
                        {host.minPrice !== null && (
                            <span className="text-xs font-semibold text-foreground shrink-0">
                                {t("features.explore.card.from")} {host.minPrice}€
                                <span className="text-muted-foreground font-normal">
                                    {t("features.explore.card.perNight")}
                                </span>
                            </span>
                        )}
                    </div>
                    <ProBadge isPro={host.isProfessional} />
                    <LocationLine host={host} />
                    <RatingBadge rating={host.rating} reviewCount={host.reviewCount} />
                    <PetIcons types={host.animalTypes} />
                </div>
            </button>
        );
    }

    return (
        <div
            data-slot="host-card"
            role="button"
            tabIndex={0}
            onClick={handleClick}
            onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && handleClick()}
            className={cn(
                "flex flex-col overflow-hidden rounded-2xl bg-card border border-border/60 text-start transition-shadow hover:shadow-md shrink-0 w-44 cursor-pointer",
                className,
            )}
        >
            <div className="relative h-32 w-full bg-muted flex items-center justify-center overflow-hidden">
                <HostAvatar url={avatarUrl} name={host.name} />
                <div className="absolute top-2 start-2">
                    <ProBadge isPro={host.isProfessional} />
                </div>
                <button
                    type="button"
                    aria-label={t("features.explore.card.addToFavorites")}
                    onClick={(e) => e.stopPropagation()}
                    className="absolute top-2 end-2 size-7 rounded-full bg-background/80 backdrop-blur-sm flex items-center justify-center shadow-sm"
                >
                    <span className="text-[13px] leading-none">♡</span>
                </button>
            </div>
            <div className="flex flex-col gap-1 p-2.5">
                <span className="font-semibold text-sm line-clamp-1">{host.name}</span>
                <LocationLine host={host} />
                <RatingBadge rating={host.rating} reviewCount={host.reviewCount} />
                <PetIcons types={host.animalTypes} />
                {host.minPrice !== null && (
                    <div className="flex items-center justify-between mt-0.5">
                        <span className="text-xs font-semibold text-foreground">
                            {t("features.explore.card.from")} {host.minPrice}€
                            <span className="text-muted-foreground font-normal">
                                {t("features.explore.card.perNight")}
                            </span>
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}
