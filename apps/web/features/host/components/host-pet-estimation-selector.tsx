"use client";

import { useTranslations } from "next-intl";
import Link from "next/link";
import { Check, PawPrint } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Button } from "@workspace/ui/components/button";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { cn } from "@workspace/ui/lib/utils";
import type { PetModel } from "@workspace/modules/pets";

function chipClassName(isDisabled: boolean, isSelected: boolean): string {
    if (isDisabled) {
        return "cursor-not-allowed border-border text-slate-400 opacity-60";
    }
    if (isSelected) {
        return "border-primary bg-primary/5 text-slate-900";
    }
    return "border-border text-slate-700 hover:bg-muted";
}

type HostPetEstimationSelectorProps = {
    pets: PetModel[];
    selectedPetIds: string[];
    onToggle: (petId: string) => void;
    disabledPetIds?: string[];
    hiddenCount?: number;
    emptyHref?: string;
    hideHeader?: boolean;
};

export function HostPetEstimationSelector({
    pets,
    selectedPetIds,
    onToggle,
    disabledPetIds = [],
    hiddenCount = 0,
    emptyHref,
    hideHeader = false,
}: HostPetEstimationSelectorProps) {
    const t = useTranslations();

    const header = hideHeader ? null : (
        <div className="flex flex-col">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.estimateTitle")}
            </h2>
            <p className="text-sm text-muted-foreground">
                {t("features.host.detail.estimateDescription")}
            </p>
        </div>
    );

    if (pets.length === 0) {
        if (!emptyHref) {
            return null;
        }
        return (
            <section data-slot="host-pet-estimation-selector" className="flex flex-col gap-3">
                {header}
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <PawPrint />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.petEmptyTitle")}</EmptyTitle>
                        <EmptyDescription>
                            {hiddenCount > 0
                                ? t("features.host.detail.petHiddenNote", { count: hiddenCount })
                                : t("features.host.detail.petEmptyDescription")}
                        </EmptyDescription>
                    </EmptyHeader>
                    <Button asChild variant="secondary" className="rounded-full">
                        <Link href={emptyHref}>{t("features.host.detail.petManage")}</Link>
                    </Button>
                </Empty>
            </section>
        );
    }

    const hasDisabled = pets.some((pet) => disabledPetIds.includes(pet.id));

    return (
        <section data-slot="host-pet-estimation-selector" className="flex flex-col gap-3">
            {header}
            <div className="flex flex-wrap gap-2">
                {pets.map((pet) => {
                    const isSelected = selectedPetIds.includes(pet.id);
                    const isDisabled = disabledPetIds.includes(pet.id);
                    return (
                        <button
                            key={pet.id}
                            type="button"
                            disabled={isDisabled}
                            onClick={() => onToggle(pet.id)}
                            aria-pressed={isSelected}
                            className={cn(
                                "flex items-center gap-2 rounded-4xl border py-1 pe-3 ps-1 text-sm transition-colors",
                                chipClassName(isDisabled, isSelected),
                            )}
                        >
                            <Avatar className="size-6">
                                {pet.avatarUrl && (
                                    <AvatarImage src={pet.avatarUrl} alt={pet.name} />
                                )}
                                <AvatarFallback>{pet.name.charAt(0).toUpperCase()}</AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{pet.name}</span>
                            {isSelected && !isDisabled && <Check className="size-4 text-primary" />}
                        </button>
                    );
                })}
            </div>
            {(hasDisabled || hiddenCount > 0) && (
                <div className="flex flex-col gap-1">
                    {hasDisabled && (
                        <p className="text-xs text-muted-foreground">
                            {t("features.host.detail.petDisabledNote")}
                        </p>
                    )}
                    {hiddenCount > 0 && (
                        <p className="text-xs text-muted-foreground">
                            {t("features.host.detail.petHiddenNote", { count: hiddenCount })}
                        </p>
                    )}
                </div>
            )}
        </section>
    );
}
