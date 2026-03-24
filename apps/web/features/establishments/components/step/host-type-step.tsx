"use client";

import { useTranslations } from "next-intl";
import { Building2, User } from "lucide-react";
import { ChoiceCards, type ChoiceCardOption } from "@workspace/ui/components/choice-cards";
import { StepShell } from "./step-shell";

export type HostType = "professional" | "individual";

type HostTypeStepProps = {
    value: HostType | null;
    onChange: (type: HostType) => void;
    error?: string;
};

export function HostTypeStep({ value, onChange, error }: HostTypeStepProps) {
    const t = useTranslations();

    const options: ChoiceCardOption<HostType>[] = [
        {
            value: "professional",
            icon: Building2,
            label: t("features.become-host.steps.hostType.professional"),
            description: t("features.become-host.steps.hostType.professionalDescription"),
        },
        {
            value: "individual",
            icon: User,
            label: t("features.become-host.steps.hostType.individual"),
            description: t("features.become-host.steps.hostType.individualDescription"),
        },
    ];

    return (
        <StepShell
            title={t("features.become-host.steps.hostType.title")}
            subtitle={t("features.become-host.steps.hostType.subtitle")}
        >
            <ChoiceCards
                mode="single"
                options={options}
                value={value}
                onValueChange={onChange}
                error={error}
                layout="list"
            />
        </StepShell>
    );
}
