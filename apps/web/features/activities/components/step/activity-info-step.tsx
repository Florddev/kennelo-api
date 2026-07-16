"use client";

import { Control } from "react-hook-form";
import { useTranslations } from "next-intl";
import { InfoSquare, TextSquare } from "@solar-icons/react";
import { InlineController } from "@/components/forms/inline-controller";
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
            <InlineController
                name="name"
                control={control}
                type="text"
                label={t("common.fields.activityName")}
                placeholder={t("common.placeholders.activityName")}
                Icon={TextSquare}
                isLoading={isLoading}
            />
            <InlineController
                name="description"
                control={control}
                type="textarea"
                label={t("common.fields.description")}
                placeholder={t("common.placeholders.description")}
                Icon={InfoSquare}
                isLoading={isLoading}
            />
        </StepShell>
    );
}
