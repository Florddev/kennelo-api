"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import type { CreateEstablishmentInput } from "@workspace/modules/establishments";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";

type EstablishmentInfoStepProps = {
    control: Control<CreateEstablishmentInput>;
    isLoading: boolean;
};

export function EstablishmentInfoStep({ control, isLoading }: EstablishmentInfoStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.establishmentInfo.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.establishmentInfo.subtitle")}
                </p>
            </div>
            <div className="flex flex-col gap-5">
                <InputController
                    name="name"
                    control={control}
                    label={t("common.fields.establishmentName")}
                    placeholder={t("common.placeholders.establishmentName")}
                    isLoading={isLoading}
                />
                <TextareaController
                    name="description"
                    control={control}
                    label={t("common.fields.description")}
                    placeholder={t("common.placeholders.description")}
                    isLoading={isLoading}
                    rows={5}
                />
            </div>
        </div>
    );
}
