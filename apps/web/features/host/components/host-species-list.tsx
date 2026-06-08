import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";
import type { ActivityCycleSettingModel } from "@workspace/modules/activities";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type HostSpeciesListProps = {
    capacities: ActivityCycleSettingModel[];
};

export function HostSpeciesList({ capacities }: HostSpeciesListProps) {
    const t = useTranslations();

    if (capacities.length === 0) {
        return (
            <Empty className="rounded-2xl border py-8">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <PawPrint />
                    </EmptyMedia>
                    <EmptyTitle>{t("features.host.detail.speciesEmpty")}</EmptyTitle>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div data-slot="host-species-list" className="flex flex-wrap items-center gap-x-6 gap-y-4">
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
                            {t("features.host.detail.spotsAvailable", {
                                count: capacity.availableSpots,
                            })}
                        </span>
                    </div>
                </div>
            ))}
        </div>
    );
}
