import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";
import { Skeleton } from "@workspace/ui/components/skeleton";
import type { AnimalTypePriceRangeModel } from "@workspace/modules/activities";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type HostSpeciesListProps = {
    priceRanges: AnimalTypePriceRangeModel[];
    isLoading: boolean;
};

export function HostSpeciesList({ priceRanges, isLoading }: HostSpeciesListProps) {
    const t = useTranslations();

    if (isLoading) {
        return (
            <div className="flex flex-wrap items-center gap-x-6 gap-y-4">
                {Array.from({ length: 2 }).map((_, index) => (
                    <div key={index} className="flex items-center gap-3">
                        <Skeleton className="size-8 rounded-full" />
                        <div className="flex flex-col gap-1.5">
                            <Skeleton className="h-3 w-16" />
                            <Skeleton className="h-3 w-20" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (priceRanges.length === 0) {
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
            {priceRanges.map((range) => (
                <div key={range.animalType.id} className="flex items-center gap-3">
                    <PetTypeIllustration
                        code={range.animalType.code}
                        name={range.animalType.name}
                        className="size-8"
                    />
                    <div className="flex flex-col gap-0.5 text-xs">
                        <span className="font-semibold text-slate-700">
                            {range.animalType.name}
                        </span>
                        <span className="text-slate-600">
                            {range.minPrice === range.maxPrice
                                ? t("features.host.detail.priceSingle", {
                                      price: Math.round(range.minPrice),
                                  })
                                : t("features.host.detail.priceRange", {
                                      min: Math.round(range.minPrice),
                                      max: Math.round(range.maxPrice),
                                  })}
                        </span>
                    </div>
                </div>
            ))}
        </div>
    );
}
