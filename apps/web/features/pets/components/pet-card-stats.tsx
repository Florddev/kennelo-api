"use client";

import { Cake, ChevronRight, Cpu, Mars, Syringe, Venus, Weight } from "lucide-react";
import { useTranslations } from "next-intl";
import type { ReactNode } from "react";
import { CardContent } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetTypeIllustration } from "./pet-type-illustration";

type PetCardStatsProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

function isVaccinatedAttr(attr: PetAttributeModel): boolean {
    const code = attr.attributeDefinition?.code ?? "";
    return ["vaccinated", "is_vaccinated"].includes(code) && attr.value === true;
}

function getBreedLabel(pet: PetModel): string {
    if (pet.breed) return pet.breed;
    return pet.animalType?.name ?? "—";
}

function getMicrochipKey(hasMicrochip: boolean): string {
    if (hasMicrochip) return "features.pets.badges.microchipped";
    return "features.pets.badges.noMicrochip";
}

function SexBadge({ sex }: { sex: "male" | "female" }) {
    return (
        <div
            className={cn(
                "rounded-lg flex items-center justify-center text-sm font-bold shrink-0 size-8",
                sex === "male"
                    ? "bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                    : "bg-pink-100 dark:bg-pink-900/30 text-pink-500 dark:text-pink-400",
            )}
        >
            {sex === "male" ? <Mars className="size-4.5" /> : <Venus className="size-4.5" />}
        </div>
    );
}

function InfoBox({ icon, label }: { icon: ReactNode; label: string }) {
    return (
        <div className="flex flex-col items-center gap-1.5 bg-muted rounded-2xl py-3 px-1">
            {icon}
            <span className="text-xs font-semibold text-center leading-tight w-full truncate px-1">
                {label}
            </span>
        </div>
    );
}

function StatusBadge({
    icon,
    label,
    className,
}: {
    icon: ReactNode;
    label: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                "flex items-center gap-1 rounded-4xl px-2 py-0.5 text-[10px] font-medium",
                className,
            )}
        >
            {icon}
            <span>{label}</span>
        </div>
    );
}

type PetInfoBoxesProps = {
    ageDisplay: string | null;
    weight: number | null;
    microchipLabel: string;
};

function PetInfoBoxes({ ageDisplay, weight, microchipLabel }: PetInfoBoxesProps) {
    const weightLabel = weight ? `${weight} kg` : "—";
    return (
        <div className="grid grid-cols-3 gap-2">
            <InfoBox
                icon={<Cake className="size-4 text-muted-foreground" />}
                label={ageDisplay ?? "—"}
            />
            <InfoBox
                icon={<Weight className="size-4 text-muted-foreground" />}
                label={weightLabel}
            />
            <InfoBox
                icon={<Cpu className="size-4 text-muted-foreground" />}
                label={microchipLabel}
            />
        </div>
    );
}

export function PetCardStats({ pet, ageDisplay }: PetCardStatsProps) {
    const t = useTranslations();

    const isVaccinated = pet.attributes?.some(isVaccinatedAttr);
    const breedLabel = getBreedLabel(pet);

    return (
        <CardContent className="p-3.5 space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="flex gap-2 items-center min-w-0">
                    <PetTypeIllustration
                        code={pet.animalType?.code || ""}
                        name={pet.name}
                        className="size-8"
                    />
                    <div className="min-w-0">
                        <h3 className="font-semibold text-base leading-tight truncate">
                            {pet.name}
                        </h3>
                        <p className="text-xs text-muted-foreground truncate mt-0.5">
                            {breedLabel}
                        </p>
                    </div>
                </div>
                {pet.sex && pet.sex !== "unknown" && <SexBadge sex={pet.sex} />}
            </div>

            <PetInfoBoxes
                ageDisplay={ageDisplay}
                weight={pet.weight}
                microchipLabel={t(getMicrochipKey(pet.hasMicrochip))}
            />

            {isVaccinated && (
                <div className="flex items-center gap-1.5 flex-wrap">
                    <StatusBadge
                        icon={<Syringe className="size-3 shrink-0" />}
                        label={t("features.pets.badges.vaccinationsUpToDate")}
                        className="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
                    />
                </div>
            )}

            <div className="flex items-center justify-between bg-primary/8 rounded-2xl px-3.5 py-2.5">
                <span className="text-sm font-medium text-primary">
                    {t("features.pets.profile.viewProfile")}
                </span>
                <ChevronRight className="size-4 text-primary shrink-0" />
            </div>
        </CardContent>
    );
}
