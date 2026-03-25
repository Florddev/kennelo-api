"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { StepShell } from "./step-shell";

type EstablishmentInfoStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function EstablishmentInfoStep({ control, isLoading }: EstablishmentInfoStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.establishmentInfo.title")}
            subtitle={t("features.become-host.steps.establishmentInfo.subtitle")}
        >
            <InputController
                name="name"
                control={control}
                label={t("common.fields.establishmentName")}
                placeholder={t("common.placeholders.establishmentName")}
                isLoading={isLoading}
                type="text"
            />
            <TextareaController
                name="description"
                control={control}
                label={t("common.fields.description")}
                placeholder={t("common.placeholders.description")}
                isLoading={isLoading}
                rows={5}
            />
        </StepShell>
    );
}
