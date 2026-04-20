import { useTranslations } from "next-intl";
import { CapacityModel } from "@workspace/modules/establishments";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type SpeciesListProps = {
    capacities: CapacityModel[];
};

export function SpeciesList({ capacities }: SpeciesListProps) {
    const t = useTranslations();

    if (capacities.length === 0) {
        return (
            <p className="text-sm italic text-slate-500">
                {t("features.explore.detail.speciesEmpty")}
            </p>
        );
    }

    return (
        <div data-slot="species-list" className="flex flex-wrap items-center gap-x-6 gap-y-4">
            {capacities.map((capacity) => (
                <div key={capacity.id} className="flex items-center gap-3">
                    <PetTypeIllustration
                        code={capacity.animalType.code}
                        name={capacity.animalType.name}
                        className="size-8"
                    />
                    <div className="flex flex-col gap-0.5 text-xs">
                        <span className="font-semibold text-slate-700">
                            {capacity.animalType.name}
                        </span>
                        <span className="text-slate-600">
                            {t("features.explore.detail.spotsAvailable", {
                                count: capacity.availableSpots,
                            })}
                        </span>
                    </div>
                </div>
            ))}
        </div>
    );
}
