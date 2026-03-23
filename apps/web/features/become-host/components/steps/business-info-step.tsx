"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { InputController } from "@/components/forms/input-controller";

type BusinessInfoStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function BusinessInfoStep({ control, isLoading }: BusinessInfoStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.businessInfo.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.businessInfo.subtitle")}
                </p>
            </div>
            <div className="flex flex-col gap-5">
                <InputController
                    name="siret"
                    control={control}
                    label={t("common.fields.siret")}
                    placeholder={t("common.placeholders.siret")}
                    isLoading={isLoading}
                />
            </div>
        </div>
    );
}
