import { useTranslations } from "next-intl";
import type { AddressModel } from "@workspace/modules/address";
import type { ActivityCycleSettingModel } from "@workspace/modules/activities";

type HostHeaderSectionProps = {
    name: string;
    address: AddressModel | null;
    capacities: ActivityCycleSettingModel[];
};

export function HostHeaderSection({ name, address, capacities }: HostHeaderSectionProps) {
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
        <section className="flex flex-col gap-3 pt-3">
            <h1 className="text-xl font-semibold text-slate-900">{name}</h1>
            {pensionLine && <p className="text-xs text-zinc-500">{pensionLine}</p>}
        </section>
    );
}
