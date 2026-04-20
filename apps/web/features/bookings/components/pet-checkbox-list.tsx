"use client";

import { useTranslations } from "next-intl";
import Link from "next/link";
import type { PetModel } from "@workspace/modules/pets";
import { Checkbox } from "@workspace/ui/components/checkbox";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type PetCheckboxListProps = {
    pets: PetModel[];
    selectedIds: string[];
    onToggle: (petId: string) => void;
    managePetsHref: string;
};

export function PetCheckboxList({
    pets,
    selectedIds,
    onToggle,
    managePetsHref,
}: PetCheckboxListProps) {
    const t = useTranslations();

    if (pets.length === 0) {
        return (
            <div className="flex flex-col items-start gap-3 rounded-2xl border border-dashed p-4">
                <p className="text-sm text-muted-foreground">
                    {t("features.bookings.checkout.noPets")}
                </p>
                <Button asChild variant="secondary" className="rounded-full">
                    <Link href={managePetsHref}>{t("features.bookings.checkout.goToPets")}</Link>
                </Button>
            </div>
        );
    }

    return (
        <ul className="flex flex-col gap-2">
            {pets.map((pet) => {
                const isChecked = selectedIds.includes(pet.id);
                return (
                    <li key={pet.id}>
                        <label
                            className={cn(
                                "flex cursor-pointer items-center gap-3 rounded-2xl border p-3 transition-colors",
                                isChecked
                                    ? "border-foreground bg-muted/40"
                                    : "border-border hover:bg-muted/30",
                            )}
                        >
                            <Checkbox
                                checked={isChecked}
                                onCheckedChange={() => onToggle(pet.id)}
                            />
                            {pet.animalType && (
                                <PetTypeIllustration
                                    code={pet.animalType.code}
                                    name={pet.animalType.name}
                                    className="size-8"
                                />
                            )}
                            <div className="flex flex-1 flex-col">
                                <span className="text-sm font-semibold">{pet.name}</span>
                                {pet.breed && (
                                    <span className="text-xs text-muted-foreground">
                                        {pet.breed}
                                    </span>
                                )}
                            </div>
                        </label>
                    </li>
                );
            })}
        </ul>
    );
}
