"use client";

import { useTranslations } from "next-intl";
import { Home, Heart, Sun, Shield, MoreHorizontal } from "lucide-react";
import {
    ChoiceCardContainer,
    ChoiceCardContent,
    ChoiceCardDescription,
    ChoiceCardIcon,
    ChoiceCardLabel,
    ChoiceCards,
    type ChoiceCardOption,
} from "@workspace/ui/components/choice-cards";
import { StepShell } from "./step-shell";

export type EstablishmentType = "pension" | "breeding" | "daycare" | "shelter" | "other";

type EstablishmentTypeStepProps = {
    value: EstablishmentType | null;
    onChange: (type: EstablishmentType) => void;
    error?: string;
};

export function EstablishmentTypeStep({ value, onChange, error }: EstablishmentTypeStepProps) {
    const t = useTranslations();

    const options: ChoiceCardOption<EstablishmentType>[] = [
        {
            value: "pension",
            icon: Home,
            label: t("features.become-host.steps.establishmentType.pension"),
            description: t("features.become-host.steps.establishmentType.pensionDescription"),
        },
        {
            value: "breeding",
            icon: Heart,
            label: t("features.become-host.steps.establishmentType.breeding"),
            description: t("features.become-host.steps.establishmentType.breedingDescription"),
        },
        {
            value: "daycare",
            icon: Sun,
            label: t("features.become-host.steps.establishmentType.daycare"),
            description: t("features.become-host.steps.establishmentType.daycareDescription"),
        },
        {
            value: "shelter",
            icon: Shield,
            label: t("features.become-host.steps.establishmentType.shelter"),
            description: t("features.become-host.steps.establishmentType.shelterDescription"),
        },
        {
            value: "other",
            icon: MoreHorizontal,
            label: t("features.become-host.steps.establishmentType.other"),
            description: t("features.become-host.steps.establishmentType.otherDescription"),
            className: "col-span-2",
        },
    ];

    return (
        <StepShell
            title={"Parmi les propositions suivantes, laquelle décrit le mieux votre structure ?"}
            // subtitle={t("features.become-host.steps.establishmentType.subtitle")}
        >
            <ChoiceCards
                mode="single"
                options={options}
                value={value}
                render={(option, isSelected) => (
                    <ChoiceCardContainer
                        isSelected={isSelected}
                        disabled={option.disabled}
                        className="flex-col"
                    >
                        {option.icon && (
                            <ChoiceCardIcon icon={option.icon} isSelected={isSelected} />
                        )}
                        <ChoiceCardContent>
                            <ChoiceCardLabel>{option.label}</ChoiceCardLabel>
                            {option.description && (
                                <ChoiceCardDescription>{option.description}</ChoiceCardDescription>
                            )}
                        </ChoiceCardContent>
                    </ChoiceCardContainer>
                )}
                onValueChange={onChange}
                error={error}
                layout="grid"
            />
        </StepShell>
    );
}
