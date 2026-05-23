import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { Control } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InlineController } from "@/components/forms/inline-controller";
import { CalendarMark, Cpu, InfoSquare, Scissors, Weigher } from "@solar-icons/react";

export function ProfileStep({
    control,
    isLoading,
}: {
    control: Control<CreatePetInput>;
    isLoading: boolean;
}) {
    const t = useTranslations();

    return (
        <WizardStepShell title={t("features.pets.create.steps.profile.title")}>
            <InlineController
                name="birthDate"
                control={control}
                type="date"
                label={t("features.pets.fields.birthDate")}
                Icon={CalendarMark}
                // placeholder={t("features.pets.create.placeholders.birthDate")}
                isLoading={isLoading}
                allowApproximate
            />
            <InlineController
                name="weight"
                control={control}
                type="number"
                label={t("features.pets.fields.weight")}
                Icon={Weigher}
                step={1}
                min={0}
                isLoading={isLoading}
                className="shrink-0"
            />
            <InlineController
                name="isSterilized"
                control={control}
                type="boolean"
                label={t("features.pets.fields.sterilized")}
                Icon={Scissors}
                isLoading={isLoading}
            />
            <InlineController
                name="microchipNumber"
                control={control}
                type="text"
                label={t("features.pets.fields.microchipNumber")}
                Icon={Cpu}
                // placeholder={t("features.pets.create.placeholders.microchipNumber")}
                isLoading={isLoading}
            />
            <InlineController
                name="about"
                control={control}
                type="textarea"
                label={t("features.pets.fields.about")}
                Icon={InfoSquare}
                placeholder={t("features.pets.create.placeholders.about")}
                isLoading={isLoading}
            />
        </WizardStepShell>
    );
}
