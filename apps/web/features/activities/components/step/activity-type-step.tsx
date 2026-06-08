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

import { type ActivityTypeValue } from "@workspace/modules/activities";

type ActivityTypeStepProps = {
    value: ActivityTypeValue | null;
    onChange: (type: ActivityTypeValue) => void;
    error?: string;
};

export function ActivityTypeStep({ value, onChange, error }: ActivityTypeStepProps) {
    const t = useTranslations();

    const options: SingleChoiceCardStepOption<ActivityTypeValue>[] = [
        {
            value: "boarding",
            visual: <ChoiceCardIcon icon={KFence2} />,
            label: t("features.activities.types.boarding"),
        },
        {
            value: "daycare",
            visual: <ChoiceCardIcon icon={KHomeHeart} />,
            label: t("features.activities.types.daycare"),
        },
        {
            value: "breeding",
            visual: <ChoiceCardIcon icon={KDna3} />,
            label: t("features.activities.types.breeding"),
        },
        {
            value: "pet-sitter",
            visual: <ChoiceCardIcon icon={KUserStar} />,
            label: t("features.activities.types.pet-sitter"),
        },
        {
            value: "home-care",
            visual: <ChoiceCardIcon icon={KApartment5} />,
            label: t("features.activities.types.home-care"),
        },
        {
            value: "host-family",
            visual: <ChoiceCardIcon icon={KFamilyHeart} />,
            label: t("features.activities.types.host-family"),
        },
        {
            value: "mobile-boarding",
            visual: <ChoiceCardIcon icon={KCaravan3} />,
            label: t("features.activities.types.mobile-boarding"),
        },
        {
            value: "shelter",
            visual: <ChoiceCardIcon icon={KHandTakingHeart} />,
            label: t("features.activities.types.shelter"),
        },
        // {
        //     value: "other",
        //     icon: MoreHorizontal,
        //     label: t("features.activities.types.other"),
        //     className: "col-span-2",
        // },
    ];

    return (
        <SingleChoiceCardsStep
            title={t("features.become-host.steps.activityType.title")}
            value={value}
            onChange={onChange}
            options={options}
            error={error}
        />
    );
}
