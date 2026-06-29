"use client";

import { useTranslations } from "next-intl";
import { Minus, Plus } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import type { AnimalTypePriceRangeModel } from "@workspace/modules/activities";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type HostAnimalTypeEstimationSelectorProps = {
    priceRanges: AnimalTypePriceRangeModel[];
    counts: Record<string, number>;
    onCountChange: (animalTypeId: string, count: number) => void;
};

export function HostAnimalTypeEstimationSelector({
    priceRanges,
    counts,
    onCountChange,
}: HostAnimalTypeEstimationSelectorProps) {
    const t = useTranslations();

    if (priceRanges.length === 0) {
        return null;
    }

    return (
        <section data-slot="host-animal-type-estimation-selector" className="flex flex-col gap-3">
            <div className="flex flex-col">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.estimateTypeTitle")}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t("features.host.detail.estimateTypeDescription")}
                </p>
            </div>
            <div className="flex flex-col gap-2">
                {priceRanges.map((range) => {
                    const count = counts[range.animalType.id] ?? 0;
                    return (
                        <div
                            key={range.animalType.id}
                            className="flex items-center justify-between gap-3 rounded-2xl"
                        >
                            <div className="flex items-center gap-3">
                                <PetTypeIllustration
                                    code={range.animalType.code}
                                    name={range.animalType.name}
                                    className="size-9"
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
                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    size="icon-xs"
                                    variant="outline"
                                    disabled={count === 0}
                                    aria-label={t("features.host.detail.estimateDecrease")}
                                    onClick={() => onCountChange(range.animalType.id, count - 1)}
                                >
                                    <Minus />
                                </Button>
                                <span className="w-6 text-center text-sm font-semibold tabular-nums">
                                    {count}
                                </span>
                                <Button
                                    type="button"
                                    size="icon-xs"
                                    variant="outline"
                                    aria-label={t("features.host.detail.estimateIncrease")}
                                    onClick={() => onCountChange(range.animalType.id, count + 1)}
                                >
                                    <Plus />
                                </Button>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
