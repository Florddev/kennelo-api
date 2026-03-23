"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { InputController } from "@/components/forms/input-controller";

type AddressStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function AddressStep({ control, isLoading }: AddressStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.address.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.address.subtitle")}
                </p>
            </div>
            <div className="flex flex-col gap-5">
                <InputController
                    name="address.line1"
                    control={control}
                    label={t("common.fields.addressLine1")}
                    placeholder={t("common.placeholders.addressLine1")}
                    isLoading={isLoading}
                />
                <InputController
                    name="address.line2"
                    control={control}
                    label={t("common.fields.addressLine2")}
                    placeholder={t("common.placeholders.addressLine2")}
                    isLoading={isLoading}
                />
                <div className="grid grid-cols-2 gap-4">
                    <InputController
                        name="address.city"
                        control={control}
                        label={t("common.fields.city")}
                        placeholder={t("common.placeholders.city")}
                        isLoading={isLoading}
                    />
                    <InputController
                        name="address.postalCode"
                        control={control}
                        label={t("common.fields.postalCode")}
                        placeholder={t("common.placeholders.postalCode")}
                        isLoading={isLoading}
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <InputController
                        name="address.region"
                        control={control}
                        label={t("common.fields.region")}
                        placeholder={t("common.placeholders.region")}
                        isLoading={isLoading}
                    />
                    <InputController
                        name="address.country"
                        control={control}
                        label={t("common.fields.country")}
                        placeholder={t("common.placeholders.country")}
                        isLoading={isLoading}
                    />
                </div>
            </div>
        </div>
    );
}
