"use client";

import { useMemo, useState } from "react";
import { Control, useFormContext, useWatch } from "react-hook-form";
import { useTranslations } from "next-intl";
import { MapPin } from "lucide-react";
import { Global, Streets, MapPoint, Mailbox, Signpost2 } from "@solar-icons/react";
import { Map, MapControls, MapMarker, MarkerContent } from "@workspace/ui/components/mapcn";
import { InlineController, type InlineOption } from "@/components/forms/inline-controller";
import type { CreateActivityInput } from "@workspace/modules/activities";
import type { AddressSuggestionModel } from "@workspace/modules/address";
import { StepShell } from "./step-shell";
import { AddressSearchField } from "./address-search-field";

const COUNTRY_CODES = ["FR", "BE", "DE", "IT", "ES", "NL", "LU", "CH"] as const;

const FALLBACK_CENTER: [number, number] = [2.2137, 46.2276];
const FALLBACK_ZOOM = 4.5;
const SELECTED_ZOOM = 16;

type AddressStepProps = {
    control: Control<CreateActivityInput>;
    isLoading: boolean;
};

export function AddressStep({ control, isLoading }: AddressStepProps) {
    const t = useTranslations();
    const { setValue } = useFormContext<CreateActivityInput>();
    const address = useWatch({ control, name: "address" });

    const countryOptions = useMemo<InlineOption[]>(
        () =>
            COUNTRY_CODES.map((code) => ({
                value: code,
                label: t(`common.countries.${code.toLowerCase()}`),
            })),
        [t],
    );

    const latitude = address?.latitude ?? null;
    const longitude = address?.longitude ?? null;
    const hasCoordinates = latitude !== null && longitude !== null;

    const [viewport, setViewport] = useState<{ center: [number, number]; zoom: number }>(() =>
        latitude !== null && longitude !== null
            ? { center: [longitude, latitude], zoom: SELECTED_ZOOM }
            : { center: FALLBACK_CENTER, zoom: FALLBACK_ZOOM },
    );

    const handleSelect = (suggestion: AddressSuggestionModel) => {
        setValue("address.line1", suggestion.line1, { shouldValidate: true });
        setValue("address.city", suggestion.city, { shouldValidate: true });
        setValue("address.postalCode", suggestion.postalCode, { shouldValidate: true });
        setValue("address.region", suggestion.region);
        setValue("address.latitude", suggestion.latitude);
        setValue("address.longitude", suggestion.longitude);

        if (suggestion.country) {
            setValue("address.country", suggestion.country, { shouldValidate: true });
        }

        setViewport({ center: [suggestion.longitude, suggestion.latitude], zoom: SELECTED_ZOOM });
    };

    return (
        <StepShell
            title={t("features.become-host.steps.address.title")}
            subtitle={t("features.become-host.steps.address.subtitle")}
        >
            <div className="relative h-72 w-full overflow-hidden rounded-2xl border">
                <Map viewport={viewport} onViewportChange={setViewport}>
                    {hasCoordinates && (
                        <MapMarker longitude={longitude} latitude={latitude}>
                            <MarkerContent>
                                <MapPin className="size-8 fill-primary/30 text-primary" />
                            </MarkerContent>
                        </MapMarker>
                    )}
                    <MapControls position="bottom-right" />
                </Map>

                <div className="absolute inset-x-3 top-3 z-10">
                    <AddressSearchField
                        onSelect={handleSelect}
                        placeholder={t("features.become-host.steps.address.searchPlaceholder")}
                        emptyLabel={t("features.become-host.steps.address.searchEmpty")}
                    />
                </div>
            </div>

            <InlineController
                name="address.line1"
                control={control}
                type="text"
                label={t("common.fields.addressLine1")}
                placeholder={t("common.placeholders.addressLine1")}
                Icon={Streets}
                isLoading={isLoading}
            />
            <InlineController
                name="address.line2"
                control={control}
                type="text"
                label={t("common.fields.addressLine2")}
                placeholder={t("common.placeholders.addressLine2")}
                Icon={Signpost2}
                isLoading={isLoading}
            />
            <InlineController
                name="address.city"
                control={control}
                type="text"
                label={t("common.fields.city")}
                placeholder={t("common.placeholders.city")}
                Icon={MapPoint}
                isLoading={isLoading}
            />
            <InlineController
                name="address.postalCode"
                control={control}
                type="text"
                label={t("common.fields.postalCode")}
                placeholder={t("common.placeholders.postalCode")}
                Icon={Mailbox}
                isLoading={isLoading}
            />
            <InlineController
                name="address.region"
                control={control}
                type="text"
                label={t("common.fields.region")}
                placeholder={t("common.placeholders.region")}
                Icon={MapPoint}
                isLoading={isLoading}
            />
            <InlineController
                name="address.country"
                control={control}
                type="list"
                label={t("common.fields.country")}
                placeholder={t("common.placeholders.country")}
                Icon={Global}
                options={countryOptions}
                isLoading={isLoading}
            />
        </StepShell>
    );
}
