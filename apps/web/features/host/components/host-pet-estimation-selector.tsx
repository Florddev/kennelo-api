"use client";

import { useTranslations } from "next-intl";
import { Check } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";
import type { PetModel } from "@workspace/modules/pets";

type HostPetEstimationSelectorProps = {
    pets: PetModel[];
    selectedPetIds: string[];
    onToggle: (petId: string) => void;
};

export function HostPetEstimationSelector({
    pets,
    selectedPetIds,
    onToggle,
}: HostPetEstimationSelectorProps) {
    const t = useTranslations();

    if (pets.length === 0) {
        return null;
    }

    return (
        <section data-slot="host-pet-estimation-selector" className="flex flex-col gap-3">
            <div className="flex flex-col">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.estimateTitle")}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t("features.host.detail.estimateDescription")}
                </p>
            </div>
            <div className="flex flex-wrap gap-2">
                {pets.map((pet) => {
                    const isSelected = selectedPetIds.includes(pet.id);
                    return (
                        <button
                            key={pet.id}
                            type="button"
                            onClick={() => onToggle(pet.id)}
                            aria-pressed={isSelected}
                            className={cn(
                                "flex items-center gap-2 rounded-4xl border py-1.5 pe-3 ps-1.5 text-sm transition-colors",
                                isSelected
                                    ? "border-primary bg-primary/5 text-slate-900"
                                    : "border-border text-slate-700 hover:bg-muted",
                            )}
                        >
                            <Avatar className="size-7">
                                {pet.avatarUrl && (
                                    <AvatarImage src={pet.avatarUrl} alt={pet.name} />
                                )}
                                <AvatarFallback>{pet.name.charAt(0).toUpperCase()}</AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{pet.name}</span>
                            {isSelected && <Check className="size-4 text-primary" />}
                        </button>
                    );
                })}
            </div>
        </section>
    );
}
