"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InputController } from "@/components/forms/input-controller";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { StepShell } from "./step-shell";

type AddressStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function AddressStep({ control, isLoading }: AddressStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.address.title")}
            subtitle={t("features.become-host.steps.address.subtitle")}
        >
            <InputController
                name="address.line1"
                control={control}
                label={t("common.fields.addressLine1")}
                placeholder={t("common.placeholders.addressLine1")}
                isLoading={isLoading}
                type="text"
            />
            <InputController
                name="address.line2"
                control={control}
                label={t("common.fields.addressLine2")}
                placeholder={t("common.placeholders.addressLine2")}
                isLoading={isLoading}
                type="text"
            />
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="address.city"
                    control={control}
                    label={t("common.fields.city")}
                    placeholder={t("common.placeholders.city")}
                    isLoading={isLoading}
                    type="text"
                />
                <InputController
                    name="address.postalCode"
                    control={control}
                    label={t("common.fields.postalCode")}
                    placeholder={t("common.placeholders.postalCode")}
                    isLoading={isLoading}
                    type="text"
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
                />
                <InputController
                    name="address.country"
                    control={control}
                    label={t("common.fields.country")}
                    placeholder={t("common.placeholders.country")}
                    isLoading={isLoading}
                    type="text"
                />
            </div>
        </StepShell>
    );
}
