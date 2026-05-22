"use client";

import Image from "next/image";
import { useTranslations } from "next-intl";
import { Minus, Plus } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";

import type { PetCounts, PetType } from "../../lib/types";
import { PET_TYPES } from "../../lib/constants";

type PetsPanelProps = {
    petCounts: PetCounts;
    onAdjust: (type: PetType, delta: number) => void;
    className?: string;
};

function PetCounter({
    type,
    label,
    description,
    count,
    onAdjust,
}: {
    type: PetType;
    label: string;
    description: string;
    count: number;
    onAdjust: (delta: number) => void;
}) {
    return (
        <div className="flex items-center gap-3.5">
            <div
                className={cn(
                    "size-12 rounded-xl flex items-center justify-center shrink-0 transition-colors",
                    count > 0 ? "bg-secondary/15" : "bg-muted",
                )}
            >
                <Image src={`/illustrations/pets/${type}.svg`} width={30} height={30} alt={label} />
            </div>
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold">{label}</div>
                <div className="text-xs text-muted-foreground">{description}</div>
            </div>
            <div className="flex items-center gap-2 shrink-0">
                <button
                    onClick={() => onAdjust(-1)}
                    disabled={count === 0}
                    className={cn(
                        "size-7 rounded-full border flex items-center justify-center transition-all",
                        count > 0
                            ? "border-primary/50 hover:border-primary"
                            : "border-border opacity-30 cursor-not-allowed",
                    )}
                >
                    <Minus size={13} />
                </button>
                <span
                    className={cn(
                        "text-sm font-semibold w-5 text-center tabular-nums transition-colors",
                        count > 0 ? "text-foreground" : "text-muted-foreground",
                    )}
                >
                    {count}
                </span>
                <button
                    onClick={() => onAdjust(1)}
                    className="size-7 rounded-full border border-border flex items-center justify-center hover:border-primary transition-colors"
                >
                    <Plus size={13} />
                </button>
            </div>
        </div>
    );
}

export function PetsPanel({ petCounts, onAdjust, className }: PetsPanelProps) {
    const t = useTranslations();

    return (
        <div
            data-slot="search-bar-pets-panel"
            className={cn(
                "absolute z-50 top-full mt-3 end-0 w-80 bg-card rounded-2xl shadow-2xl ring-1 ring-border p-5 overflow-auto max-h-128",
                className,
            )}
        >
            {PET_TYPES.map((type, i) => (
                <div key={type}>
                    {i > 0 && <div className="h-px bg-border my-3.5" />}
                    <PetCounter
                        type={type}
                        label={t(`features.search.pets.${type}`)}
                        description={t(`features.search.pets.${type}-description`)}
                        count={petCounts[type]}
                        onAdjust={(delta) => onAdjust(type, delta)}
                    />
                </div>
            ))}
        </div>
    );
}
