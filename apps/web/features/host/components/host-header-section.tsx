import { useTranslations } from "next-intl";
import { Star } from "lucide-react";
import type { AddressModel } from "@workspace/modules/address";
import type { ActivityCycleSettingModel } from "@workspace/modules/activities";

type HostHeaderSectionProps = {
    name: string;
    address: AddressModel | null;
    capacities: ActivityCycleSettingModel[];
    averageRating: number | null;
    reviewCount: number;
};

export function HostHeaderSection({
    name,
    address,
    capacities,
    averageRating,
    reviewCount,
}: HostHeaderSectionProps) {
    const t = useTranslations();
    const speciesLabel = capacities
        .map((capacity) => capacity.animalType.name.toLowerCase())
        .join(", ");

    let pensionLine: string | null = null;
    if (address) {
        pensionLine = speciesLabel
            ? t("features.host.detail.pensionLabel", {
                  species: speciesLabel,
                  city: address.city,
                  country: address.country,
              })
            : t("features.host.detail.pensionLabelFallback", {
                  city: address.city,
                  country: address.country,
              });
    }

    return (
        <section data-slot="host-header-section" className="flex flex-col gap-3 pt-3">
            <h1 className="text-xl font-semibold text-slate-900">{name}</h1>
            <div className="flex flex-col gap-1">
                {averageRating !== null && (
                    <span className="flex items-center gap-1 text-sm font-medium text-slate-900">
                        <Star className="size-4 fill-amber-400 stroke-amber-400" />
                        {averageRating.toFixed(1)}
                        <span className="text-zinc-400">
                            {t("features.host.detail.reviewsCount", { count: reviewCount })}
                        </span>
                    </span>
                )}
                {pensionLine && <p className="text-xs text-zinc-500">{pensionLine}</p>}
            </div>
        </section>
    );
}
