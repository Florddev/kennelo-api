"use client";

import { useTranslations } from "next-intl";
import { ChoiceCardIcon } from "@workspace/ui/components/choice-cards";
import {
    SingleChoiceCardsStep,
    type SingleChoiceCardStepOption,
} from "@/components/forms/stepper/single-choice-cards-step";
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

    const options: SingleChoiceCardStepOption<EstablishmentType>[] = [
        {
            value: "boarding",
            visual: <ChoiceCardIcon icon={KFence2} />,
            label: t("features.become-host.steps.establishmentType.pension"),
        },
        {
            value: "daycare",
            visual: <ChoiceCardIcon icon={KHomeHeart} />,
            label: t("features.become-host.steps.establishmentType.daycare"),
        },
        {
            value: "breeding",
            visual: <ChoiceCardIcon icon={KDna3} />,
            label: t("features.become-host.steps.establishmentType.breeding"),
        },
        {
            value: "pet-sitter",
            visual: <ChoiceCardIcon icon={KUserStar} />,
            label: t("features.become-host.steps.establishmentType.pet-sitter"),
        },
        {
            value: "home-care",
            visual: <ChoiceCardIcon icon={KApartment5} />,
            label: t("features.become-host.steps.establishmentType.home-care"),
        },
        {
            value: "host-family",
            visual: <ChoiceCardIcon icon={KFamilyHeart} />,
            label: t("features.become-host.steps.establishmentType.host-family"),
        },
        {
            value: "mobile-boarding",
            visual: <ChoiceCardIcon icon={KCaravan3} />,
            label: t("features.become-host.steps.establishmentType.mobile-boarding"),
        },
        {
            value: "shelter",
            visual: <ChoiceCardIcon icon={KHandTakingHeart} />,
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
        <SingleChoiceCardsStep
            title={t("features.become-host.steps.establishmentType.title")}
            value={value}
            onChange={onChange}
            options={options}
            error={error}
        />
    );
}
