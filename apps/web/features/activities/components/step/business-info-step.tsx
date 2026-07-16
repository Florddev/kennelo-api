"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { Buildings2 } from "@solar-icons/react";
import { InlineController } from "@/components/forms/inline-controller";
import type { CreateActivityInput } from "@workspace/modules/activities";
import { StepShell } from "./step-shell";

type BusinessInfoStepProps = {
    control: Control<CreateActivityInput>;
    isLoading: boolean;
};

export function BusinessInfoStep({ control, isLoading }: BusinessInfoStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.businessInfo.title")}
            subtitle={t("features.become-host.steps.businessInfo.subtitle")}
        >
            <InlineController
                name="siret"
                control={control}
                type="text"
                label={t("common.fields.siret")}
                placeholder={t("common.placeholders.siret")}
                Icon={Buildings2}
                isLoading={isLoading}
            />
        </StepShell>
    );
}
