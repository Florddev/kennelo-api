import { useTranslations } from "next-intl";
import type { AnimalTypePriceRangeModel } from "@workspace/modules/activities";

import { HostSpeciesList } from "./host-species-list";

type HostSpeciesSectionProps = {
    priceRanges: AnimalTypePriceRangeModel[];
    isLoading: boolean;
};

export function HostSpeciesSection({ priceRanges, isLoading }: HostSpeciesSectionProps) {
    const t = useTranslations();
    return (
        <section data-slot="host-species-section" className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.speciesAccepted")}
            </h2>
            <HostSpeciesList priceRanges={priceRanges} isLoading={isLoading} />
        </section>
    );
}
