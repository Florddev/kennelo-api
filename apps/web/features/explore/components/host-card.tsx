"use client";

import { cn } from "@workspace/ui/lib/utils";
import { Star, MapPin, Image as ImageIcon } from "lucide-react";

import type { MockHost } from "../lib/mock-hosts";

type HostCardProps = {
    host: MockHost;
    variant?: "vertical" | "horizontal";
    highlighted?: boolean;
    className?: string;
    onClick?: () => void;
};

function ProBadge({ type }: { type: MockHost["type"] }) {
    return (
        <div
            className={cn(
                "flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold backdrop-blur-sm",
                type === "pro"
                    ? "bg-foreground/85 text-background"
                    : "bg-background/85 text-foreground border border-border/40",
            )}
        >
            <span
                className={cn(
                    "size-2 rounded-full shrink-0",
                    type === "pro" ? "bg-secondary" : "bg-muted-foreground",
                )}
            />
            {type === "pro" ? "Pro certifié" : "Particulier"}
        </div>
    );
}

function PetBadges({ badges }: { badges: MockHost["petBadges"] }) {
    const visible = badges.slice(0, 3);
    const extra = badges.length - 3;

    return (
        <div className="flex items-center gap-1">
            {visible.map((badge) => (
                <div
                    key={badge.code}
                    className="size-6 rounded-full border border-border bg-muted flex items-center justify-center text-[9px] font-bold text-foreground"
                    title={badge.label}
                >
                    {badge.code}
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

export function HostCard({
    host,
    variant = "vertical",
    highlighted = false,
    className,
    onClick,
}: HostCardProps) {
    if (variant === "horizontal") {
        return (
            <button
                data-slot="host-card-horizontal"
                onClick={onClick}
                className={cn(
                    "w-full flex items-start gap-3 p-3 rounded-2xl text-start transition-colors",
                    highlighted ? "bg-secondary/8 ring-1 ring-secondary/25" : "hover:bg-muted/50",
                    className,
                )}
            >
                <div className="size-20 rounded-xl bg-muted shrink-0 overflow-hidden flex items-center justify-center">
                    <ImageIcon className="size-7 text-muted-foreground/50" />
                </div>
                <div className="flex-1 min-w-0 flex flex-col gap-1">
                    <div className="flex items-start justify-between gap-2">
                        <span className="font-semibold text-sm leading-tight line-clamp-1">
                            {host.name}
                        </span>
                        <span className="text-xs font-semibold text-foreground shrink-0">
                            dès {host.pricePerNight}€
                            <span className="text-muted-foreground font-normal">/nuit</span>
                        </span>
                    </div>
                    <ProBadge type={host.type} />
                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                        <span>{host.serviceType}</span>
                        <span>·</span>
                        <MapPin className="size-3 shrink-0" />
                        <span>à {host.distanceKm} km</span>
                    </div>
                    <div className="flex items-center justify-between mt-0.5">
                        <PetBadges badges={host.petBadges} />
                        <div className="flex items-center gap-1 text-xs">
                            <Star className="size-3 fill-foreground text-foreground" />
                            <span className="font-semibold">{host.rating}</span>
                            <span className="text-muted-foreground">· {host.reviewCount} avis</span>
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
            onClick={onClick}
            onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && onClick?.()}
            className={cn(
                "flex flex-col overflow-hidden rounded-2xl bg-card border border-border/60 text-start transition-shadow hover:shadow-md shrink-0 w-44 cursor-pointer",
                className,
            )}
        >
            <div className="relative h-32 w-full bg-muted flex items-center justify-center overflow-hidden">
                <ImageIcon className="size-8 text-muted-foreground/40" />
                <div className="absolute top-2 start-2">
                    <ProBadge type={host.type} />
                </div>
                <button
                    type="button"
                    aria-label="Ajouter aux favoris"
                    onClick={(e) => e.stopPropagation()}
                    className="absolute top-2 end-2 size-7 rounded-full bg-background/80 backdrop-blur-sm flex items-center justify-center shadow-sm"
                >
                    <span className="text-[13px] leading-none">♡</span>
                </button>
            </div>
            <div className="flex flex-col gap-1 p-2.5">
                <span className="font-semibold text-sm line-clamp-1">{host.name}</span>
                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <span>{host.serviceType}</span>
                    <span>·</span>
                    <span>{host.distanceKm} km</span>
                </div>
                <PetBadges badges={host.petBadges} />
                <div className="flex items-center justify-between mt-0.5">
                    <div className="flex items-center gap-1 text-xs">
                        <Star className="size-3 fill-foreground text-foreground" />
                        <span className="font-semibold">{host.rating}</span>
                    </div>
                    <span className="text-xs font-semibold text-foreground">
                        dès {host.pricePerNight}€
                        <span className="text-muted-foreground font-normal">/nuit</span>
                    </span>
                </div>
            </div>
        </div>
    );
}
