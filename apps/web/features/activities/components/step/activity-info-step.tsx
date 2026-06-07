"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";
import type { CreateActivityInput } from "@workspace/modules/activities";
import { StepShell } from "./step-shell";

type ActivityInfoStepProps = {
    control: Control<CreateActivityInput>;
    isLoading: boolean;
};

export function ActivityInfoStep({ control, isLoading }: ActivityInfoStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.activityInfo.title")}
            subtitle={t("features.become-host.steps.activityInfo.subtitle")}
        >
            <InputController
                name="name"
                control={control}
                label={t("common.fields.activityName")}
                placeholder={t("common.placeholders.activityName")}
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
