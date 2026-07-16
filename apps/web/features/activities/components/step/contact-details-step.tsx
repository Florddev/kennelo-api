"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { Global, Letter, Phone } from "@solar-icons/react";
import { InlineController } from "@/components/forms/inline-controller";
import type { CreateActivityInput } from "@workspace/modules/activities";
import { usePhoneCountryCode } from "@/hooks/use-phone-country-code";
import { StepShell } from "./step-shell";

type ContactDetailsStepProps = {
    control: Control<CreateActivityInput>;
    isLoading: boolean;
};

export function ContactDetailsStep({ control, isLoading }: ContactDetailsStepProps) {
    const t = useTranslations();
    const phoneCountryCode = usePhoneCountryCode();

    return (
        <StepShell
            title={t("features.become-host.steps.contactDetails.title")}
            subtitle={t("features.become-host.steps.contactDetails.subtitle")}
        >
            <InlineController
                name="phone"
                control={control}
                type="phone"
                label={t("common.fields.phone")}
                placeholder={t("common.placeholders.phone")}
                Icon={Phone}
                defaultCountry={phoneCountryCode}
                isLoading={isLoading}
            />
            <InlineController
                name="email"
                control={control}
                type="email"
                label={t("common.fields.email")}
                placeholder={t("common.placeholders.email")}
                Icon={Letter}
                isLoading={isLoading}
            />
            <InlineController
                name="website"
                control={control}
                type="url"
                label={t("common.fields.website")}
                placeholder={t("common.placeholders.website")}
                Icon={Global}
                isLoading={isLoading}
            />
        </StepShell>
    );
}
