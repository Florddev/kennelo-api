"use client";

import { useTranslations } from "next-intl";
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
import {
    KApartment5,
    KCaravan3,
    KDna3,
    KFamilyHeart,
    KFence2,
    KHandTakingHeart,
    KHomeHeart,
    KUserStar,
} from "@workspace/ui/icons";

export type EstablishmentType =
    | "boarding"
    | "breeding"
    | "daycare"
    | "shelter"
    | "other"
    | "pet-sitter"
    | "home-care"
    | "host-family"
    | "mobile-boarding";

type EstablishmentTypeStepProps = {
    value: EstablishmentType | null;
    onChange: (type: EstablishmentType) => void;
    error?: string;
};

export function EstablishmentTypeStep({ value, onChange, error }: EstablishmentTypeStepProps) {
    const t = useTranslations();

    const options: ChoiceCardOption<EstablishmentType>[] = [
        {
            value: "boarding",
            icon: KFence2,
            label: t("features.become-host.steps.establishmentType.pension"),
        },
        {
            value: "daycare",
            icon: KHomeHeart,
            label: t("features.become-host.steps.establishmentType.daycare"),
        },
        {
            value: "breeding",
            icon: KDna3,
            label: t("features.become-host.steps.establishmentType.breeding"),
        },
        {
            value: "pet-sitter",
            icon: KUserStar,
            label: t("features.become-host.steps.establishmentType.pet-sitter"),
        },
        {
            value: "home-care",
            icon: KApartment5,
            label: t("features.become-host.steps.establishmentType.home-care"),
        },
        {
            value: "host-family",
            icon: KFamilyHeart,
            label: t("features.become-host.steps.establishmentType.host-family"),
        },
        {
            value: "mobile-boarding",
            icon: KCaravan3,
            label: t("features.become-host.steps.establishmentType.mobile-boarding"),
        },
        {
            value: "shelter",
            icon: KHandTakingHeart,
            label: t("features.become-host.steps.establishmentType.shelter"),
        },
        // {
        //     value: "other",
        //     icon: MoreHorizontal,
        //     label: t("features.become-host.steps.establishmentType.other"),
        //     className: "col-span-2",
        // },
    ];

    return (
        <StepShell
            title={t("features.become-host.steps.establishmentType.title")}
            // subtitle={t("features.become-host.steps.establishmentType.subtitle")}
        >
            <ChoiceCards
                mode="single"
                options={options}
                value={value}
                optionsClassName="md:grid-cols-3"
                render={(option, isSelected) => (
                    <ChoiceCardContainer
                        isSelected={isSelected}
                        disabled={option.disabled}
                        className="flex-col h-full justify-between"
                    >
                        {option.icon && (
                            <ChoiceCardIcon icon={option.icon} {...{ secondaryOpacity: 1 }} />
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
