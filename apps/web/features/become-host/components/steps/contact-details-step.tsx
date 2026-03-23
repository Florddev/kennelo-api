"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { InputController } from "@/components/forms/input-controller";

type ContactDetailsStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function ContactDetailsStep({ control, isLoading }: ContactDetailsStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.contactDetails.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.contactDetails.subtitle")}
                </p>
            </div>
            <div className="flex flex-col gap-5">
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
            </div>
        </div>
    );
}
