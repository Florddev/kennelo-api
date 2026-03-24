"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InputController } from "@/components/forms/input-controller";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { StepShell } from "./step-shell";

type ContactDetailsStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function ContactDetailsStep({ control, isLoading }: ContactDetailsStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.contactDetails.title")}
            subtitle={t("features.become-host.steps.contactDetails.subtitle")}
        >
            <InputController
                name="phone"
                control={control}
                label={t("common.fields.phone")}
                placeholder={t("common.placeholders.phone")}
                isLoading={isLoading}
                type="phone"
            />
            <InputController
                name="email"
                control={control}
                label={t("common.fields.email")}
                placeholder={t("common.placeholders.email")}
                isLoading={isLoading}
                type="email"
            />
            <InputController
                name="website"
                control={control}
                label={t("common.fields.website")}
                placeholder={t("common.placeholders.website")}
                isLoading={isLoading}
                type="url"
            />
        </StepShell>
    );
}
