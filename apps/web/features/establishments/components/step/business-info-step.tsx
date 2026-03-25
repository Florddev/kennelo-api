"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InputController } from "@/components/forms/input-controller";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { StepShell } from "./step-shell";

type BusinessInfoStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function BusinessInfoStep({ control, isLoading }: BusinessInfoStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.businessInfo.title")}
            subtitle={t("features.become-host.steps.businessInfo.subtitle")}
        >
            <InputController
                name="siret"
                control={control}
                label={t("common.fields.siret")}
                placeholder={t("common.placeholders.siret")}
                isLoading={isLoading}
                type="text"
            />
        </StepShell>
    );
}
