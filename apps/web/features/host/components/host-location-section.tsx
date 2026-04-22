import { useTranslations } from "next-intl";
import { MapPin } from "lucide-react";
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import type { AddressModel } from "@workspace/modules/address";

type HostLocationSectionProps = {
    address: AddressModel | null;
};

export function HostLocationSection({ address }: HostLocationSectionProps) {
    const t = useTranslations();

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.location")}
            </h2>
            {address ? (
                <>
                    <p className="text-sm text-foreground">
                        {address.city}, {address.country}
                    </p>
                    <div className="relative aspect-[660/400] w-full overflow-hidden rounded-3xl bg-muted">
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="flex items-center gap-1.5 rounded-full bg-background/95 px-3 py-1.5 text-xs font-medium shadow-md">
                                <MapPin className="size-3.5 text-primary" />
                                {address.city}
                            </div>
                        </div>
                    </div>
                </>
            ) : (
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <MapPin />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.locationEmpty")}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            )}
        </section>
    );
}
