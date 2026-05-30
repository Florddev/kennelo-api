"use client";

import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";

import {
    Avatar,
    AvatarFallback,
    AvatarGroup,
    AvatarGroupCount,
} from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";
import type { CapacityModel } from "@workspace/modules/establishments";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

const MAX_VISIBLE_SPECIES = 4;

type EstablishmentSpeciesAvatarsProps = {
    capacities: CapacityModel[];
    className?: string;
};

export function EstablishmentSpeciesAvatars({
    capacities,
    className,
}: EstablishmentSpeciesAvatarsProps) {
    const t = useTranslations();

    if (capacities.length === 0) {
        return (
            <p className="text-xs text-muted-foreground italic">
                {t("features.my-establishments.card.noSpecies")}
            </p>
        );
    }

    const visible = capacities.slice(0, MAX_VISIBLE_SPECIES);
    const remaining = capacities.length - visible.length;

    return (
        <AvatarGroup data-slot="establishment-species-avatars" className={cn(className)}>
            {visible.map((capacity) => (
                <Avatar key={capacity.id} className="size-9 bg-card">
                    <AvatarFallback className="bg-muted">
                        {isIllustratedType(capacity.animalType.code) ? (
                            <PetTypeIllustration
                                code={capacity.animalType.code}
                                name={capacity.animalType.name}
                                className="size-5"
                            />
                        ) : (
                            <PawPrint className="size-4 text-muted-foreground" />
                        )}
                    </AvatarFallback>
                </Avatar>
            ))}
            {remaining > 0 && (
                <AvatarGroupCount className="size-9 text-xs font-semibold">
                    +{remaining}
                </AvatarGroupCount>
            )}
        </AvatarGroup>
    );
}
