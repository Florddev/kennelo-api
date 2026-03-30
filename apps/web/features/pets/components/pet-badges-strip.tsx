"use client";

import { Calendar, Cpu, Scissors, Weight } from "lucide-react";
import { useTranslations } from "next-intl";
import { Badge } from "@workspace/ui/components/badge";
import type { PetModel } from "@workspace/modules/pets";

type PetBadgesStripProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

export function PetBadgesStrip({ pet, ageDisplay }: PetBadgesStripProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-wrap gap-1">
            {pet.isSterilized && (
                <Badge variant="outline" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Scissors className="size-3" />
                    {t("features.pets.badges.sterilized")}
                </Badge>
            )}
            {pet.hasMicrochip && (
                <Badge variant="outline" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Cpu className="size-3" />
                    {t("features.pets.badges.microchipped")}
                </Badge>
            )}
            {pet.sex && pet.sex !== "unknown" && (
                <Badge variant="outline" className="rounded-4xl py-1">
                    {t(`features.pets.sex.${pet.sex}`)}
                </Badge>
            )}
            {ageDisplay && (
                <Badge variant="outline" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Calendar className="size-3" />
                    {ageDisplay}
                </Badge>
            )}
            {pet.weight && (
                <Badge variant="outline" className="rounded-4xl gap-1.5 py-1 ps-2">
                    <Weight className="size-3" />
                    {pet.weight} kg
                </Badge>
            )}
        </div>
    );
}
