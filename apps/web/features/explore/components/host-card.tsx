"use client";

import Image from "next/image";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { useNavigation } from "@/hooks/use-navigation";
import type { EstablishmentModel } from "@workspace/modules/establishments";
import { CrownStar, Gallery, Star, UsersGroupRounded } from "@solar-icons/react";

type HostCardProps = {
    host: EstablishmentModel;
    variant?: "vertical" | "horizontal";
    highlighted?: boolean;
    className?: string;
    onClick?: () => void;
    distanceOverride?: number | null;
};

function ProBadge({ isPro, showText = true }: { isPro: boolean; showText?: boolean }) {
    const t = useTranslations();
    return (
        <div
            className={cn(
                "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm transition-colors",
                isPro
                    ? "bg-foreground/90 text-background shadow-sm"
                    : "bg-background/85 text-foreground shadow-sm",
                !showText && "px-1.5",
            )}
        >
            {isPro ? (
                <CrownStar weight="Bold" className="size-3 text-secondary shrink-0" />
            ) : (
                <UsersGroupRounded className="size-3 shrink-0" weight="Bold" />
            )}
            {showText &&
                (isPro ? t("features.explore.card.pro") : t("features.explore.card.individual"))}
        </div>
    );
}

function Distance({
    host,
    distanceOverride,
}: {
    host: EstablishmentModel;
    distanceOverride?: number | null;
}) {
    const t = useTranslations();
    const effectiveDistance = distanceOverride !== undefined ? distanceOverride : host.distance;
    const distanceLabel =
        effectiveDistance !== null && effectiveDistance !== undefined
            ? t("features.explore.card.distanceKm", { distance: effectiveDistance })
            : null;
    const secondary = distanceLabel ?? host.address?.city ?? null;

    return (
        secondary && <span className={distanceLabel ? "shrink-0" : "truncate"}>{secondary}</span>
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
    return <Gallery className="size-8 text-muted-foreground/50" />;
}

export function HostCard({
    host,
    variant = "vertical",
    highlighted = false,
    className,
    onClick,
    distanceOverride,
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
                type="button"
                onClick={handleClick}
                className={cn(
                    "group flex w-full items-stretch gap-3 rounded-[1.75rem] text-start",
                    highlighted
                        ? "border-secondary/30 bg-secondary/5 shadow-[0_20px_40px_-30px_hsl(var(--secondary))]"
                        : "border-border/60 bg-card hover:border-border/90",
                    className,
                )}
            >
                <div className="relative flex w-24 aspect-5/4 shrink-0 overflow-hidden rounded-[1.25rem] bg-muted">
                    <div className="absolute inset-0 bg-gradient-to-br from-background/15 via-transparent to-foreground/10" />
                    <HostAvatar url={avatarUrl} name={host.name} />
                    <div className="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-foreground/35 to-transparent" />
                    <div className="absolute start-2 top-2">
                        <ProBadge isPro={host.isProfessional} showText={false} />
                    </div>
                </div>
                <div className="flex flex-col justify-between py-2">
                    <div className="min-w-0 space-y-1">
                        <div className="block line-clamp-1 text-sm font-semibold leading-tight">
                            {host.name}
                        </div>

                        <div className="flex gap-1 text-xs font-medium text-muted-foreground">
                            {t(`features.establishments.types.${host.type}`)}
                            <span>·</span>
                            <Distance host={host} distanceOverride={distanceOverride} />
                        </div>
                    </div>

                    <div className="flex gap-1 text-xs text-muted-foreground">
                        <div className="inline-flex items-center gap-0.5 text-foreground font-semibold">
                            <Star weight="Bold" className="size-3 shrink-0" />
                            <span>{host?.rating?.toFixed(1)}</span>(
                            {t("features.explore.card.reviews", { count: host?.reviewCount })})
                        </div>
                    </div>
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
                "flex w-48 shrink-0 cursor-pointer flex-col overflow-hidden text-start transition-all duration-300",
                highlighted &&
                    "border-secondary/30 bg-secondary/5 shadow-[0_28px_60px_-36px_hsl(var(--secondary))]",
                className,
            )}
        >
            <div className="relative isolate aspect-5/4 w-full overflow-hidden bg-muted rounded-[1rem] shadow-lg">
                <div className="absolute inset-0 bg-gradient-to-br from-background/10 via-transparent to-foreground/10" />
                <HostAvatar url={avatarUrl} name={host.name} />
                <div className="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-foreground/45 via-foreground/10 to-transparent" />

                {host.isProfessional && (
                    <div className="absolute start-3 top-3">
                        <div
                            className={cn(
                                "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm transition-colors bg-foreground/90 text-background shadow-sm",
                            )}
                        >
                            <CrownStar weight="Bold" className="size-3 text-secondary shrink-0" />
                            {t("features.explore.card.pro")}
                        </div>
                    </div>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-2 pt-2 px-1">
                <div className="min-w-0 space-y-1">
                    <div className="block line-clamp-1 text-sm font-semibold leading-tight">
                        {host.name}
                    </div>

                    <div className="flex gap-1 text-xs font-medium text-muted-foreground">
                        {t(`features.establishments.types.${host.type}`)}
                        <span>·</span>
                        <Distance host={host} distanceOverride={distanceOverride} />
                    </div>

                    <div className="flex gap-1 text-xs text-muted-foreground">
                        <div className="inline-flex items-center gap-0.5 text-foreground font-semibold">
                            <Star weight="Bold" className="size-3 shrink-0" />
                            <span>{host?.rating?.toFixed(1)}</span>(
                            {t("features.explore.card.reviews", { count: host?.reviewCount })})
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
