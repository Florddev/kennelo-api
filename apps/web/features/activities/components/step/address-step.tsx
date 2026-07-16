"use client";

import { useState } from "react";
import { Control, Controller, useFormContext, useWatch } from "react-hook-form";
import { useTranslations } from "next-intl";
import { MapPin } from "lucide-react";
import { Field, FieldLabel, FieldError } from "@workspace/ui/components/field";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { Map, MapControls, MapMarker, MarkerContent } from "@workspace/ui/components/mapcn";
import { InputController } from "@/components/forms/input-controller";
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

            <InputController
                name="address.line1"
                control={control}
                label={t("common.fields.addressLine1")}
                placeholder={t("common.placeholders.addressLine1")}
                isLoading={isLoading}
                type="text"
                compact
            />
            <InputController
                name="address.line2"
                control={control}
                label={t("common.fields.addressLine2")}
                placeholder={t("common.placeholders.addressLine2")}
                isLoading={isLoading}
                type="text"
                compact
            />
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="address.city"
                    control={control}
                    label={t("common.fields.city")}
                    placeholder={t("common.placeholders.city")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
                <InputController
                    name="address.postalCode"
                    control={control}
                    label={t("common.fields.postalCode")}
                    placeholder={t("common.placeholders.postalCode")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
            </div>
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="address.region"
                    control={control}
                    label={t("common.fields.region")}
                    placeholder={t("common.placeholders.region")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
                <Controller
                    name="address.country"
                    control={control}
                    render={({ field, fieldState }) => {
                        const showError =
                            fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);

                        return (
                            <Field data-invalid={showError} className="gap-1.5 group">
                                <FieldLabel htmlFor="address-country">
                                    {t("common.fields.country")}
                                </FieldLabel>
                                <Select
                                    value={field.value}
                                    onValueChange={field.onChange}
                                    disabled={isLoading}
                                >
                                    <SelectTrigger
                                        id="address-country"
                                        size="sm"
                                        className="rounded-4xl w-full"
                                        onBlur={field.onBlur}
                                    >
                                        <SelectValue
                                            placeholder={t("common.placeholders.country")}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {COUNTRY_CODES.map((code) => (
                                            <SelectItem key={code} value={code}>
                                                {t(`common.countries.${code.toLowerCase()}`)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {showError && <FieldError errors={[fieldState.error]} />}
                            </Field>
                        );
                    }}
                />
            </div>
        </StepShell>
    );
}
