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

import { type EstablishmentTypeValue } from "@workspace/modules/establishments";

type EstablishmentTypeStepProps = {
    value: EstablishmentTypeValue | null;
    onChange: (type: EstablishmentTypeValue) => void;
    error?: string;
};

export function EstablishmentTypeStep({ value, onChange, error }: EstablishmentTypeStepProps) {
    const t = useTranslations();

    const options: SingleChoiceCardStepOption<EstablishmentTypeValue>[] = [
        {
            value: "boarding",
            visual: <ChoiceCardIcon icon={KFence2} />,
            label: t("features.establishments.types.boarding"),
        },
        {
            value: "daycare",
            visual: <ChoiceCardIcon icon={KHomeHeart} />,
            label: t("features.establishments.types.daycare"),
        },
        {
            value: "breeding",
            visual: <ChoiceCardIcon icon={KDna3} />,
            label: t("features.establishments.types.breeding"),
        },
        {
            value: "pet-sitter",
            visual: <ChoiceCardIcon icon={KUserStar} />,
            label: t("features.establishments.types.pet-sitter"),
        },
        {
            value: "home-care",
            visual: <ChoiceCardIcon icon={KApartment5} />,
            label: t("features.establishments.types.home-care"),
        },
        {
            value: "host-family",
            visual: <ChoiceCardIcon icon={KFamilyHeart} />,
            label: t("features.establishments.types.host-family"),
        },
        {
            value: "mobile-boarding",
            visual: <ChoiceCardIcon icon={KCaravan3} />,
            label: t("features.establishments.types.mobile-boarding"),
        },
        {
            value: "shelter",
            visual: <ChoiceCardIcon icon={KHandTakingHeart} />,
            label: t("features.establishments.types.shelter"),
        },
        // {
        //     value: "other",
        //     icon: MoreHorizontal,
        //     label: t("features.establishments.types.other"),
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
