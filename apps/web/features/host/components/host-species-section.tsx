import { useTranslations } from "next-intl";
import type { CapacityModel } from "@workspace/modules/establishments";

import { HostSpeciesList } from "./host-species-list";

type HostSpeciesSectionProps = {
    capacities: CapacityModel[];
};

export function HostSpeciesSection({ capacities }: HostSpeciesSectionProps) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.speciesAccepted")}
            </h2>
            <HostSpeciesList capacities={capacities} />
        </section>
    );
}
