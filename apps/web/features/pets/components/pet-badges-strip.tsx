"use client";

import { useTranslations } from "next-intl";
import { Badge } from "@workspace/ui/components/badge";
import type { PetModel } from "@workspace/modules/pets";
import { Calendar, Cpu, Scissors, Weigher } from "@solar-icons/react";

type PetBadgesStripProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

export function PetBadgesStrip({ pet, ageDisplay }: PetBadgesStripProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-wrap gap-1">
            {pet.isSterilized && (
                <Badge variant="flat" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Scissors className="size-4" />
                    {t("features.pets.badges.sterilized")}
                </Badge>
            )}
            {pet.hasMicrochip && (
                <Badge variant="flat" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Cpu className="size-4" />
                    {t("features.pets.badges.microchipped")}
                </Badge>
            )}
            {pet.sex && pet.sex !== "unknown" && (
                <Badge variant="flat" className="rounded-4xl py-1">
                    {t(`features.pets.sex.${pet.sex}`)}
                </Badge>
            )}
            {ageDisplay && (
                <Badge variant="flat" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Calendar className="size-4" />
                    {ageDisplay}
                </Badge>
            )}
            {pet.weight && (
                <Badge variant="flat" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Weigher className="size-4" />
                    {pet.weight} kg
                </Badge>
            )}
        </div>
    );
}
