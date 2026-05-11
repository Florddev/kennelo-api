import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { Control } from "react-hook-form";
import { CalendarMark, Cpu, Library, Men, TextSquare, Weigher } from "@solar-icons/react";

import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InlineController } from "@/components/forms/inline-controller";
type IdentityStepProps = {
    control: Control<CreatePetInput>;
    isLoading: boolean;
};

export function IdentityStep({ control, isLoading }: IdentityStepProps) {
    const t = useTranslations();

    return (
        <WizardStepShell title={t("features.pets.create.steps.identity.title")}>
            <InlineController
                name="name"
                control={control}
                type="text"
                label={t("features.pets.fields.name")}
                Icon={TextSquare}
                // placeholder={t("features.pets.create.placeholders.name")}
                isLoading={isLoading}
            />

            <InlineController
                name="sex"
                control={control}
                type="list"
                label={t("features.pets.fields.sex")}
                Icon={Men}
                // placeholder={t("common.placeholders.select")}
                isLoading={isLoading}
                options={[
                    { label: t("features.pets.sex.male"), value: "male" },
                    { label: t("features.pets.sex.female"), value: "female" },
                    { label: t("features.pets.sex.unknown"), value: "unknown" },
                ]}
            />

            <InlineController
                name="birthDate"
                control={control}
                type="date"
                label={t("features.pets.fields.birthDate")}
                Icon={CalendarMark}
                // placeholder={t("features.pets.create.placeholders.birthDate")}
                isLoading={isLoading}
            />

            <div className="flex flex-col md:flex-row gap-2">
                <InlineController
                    name="breed"
                    control={control}
                    type="text"
                    label={t("features.pets.fields.breed")}
                    Icon={Library}
                    // placeholder={t("features.pets.create.placeholders.breed")}
                    isLoading={isLoading}
                    className="w-full"
                />
                <InlineController
                    name="weight"
                    control={control}
                    type="number"
                    label={t("features.pets.fields.weight")}
                    Icon={Weigher}
                    step={0.5}
                    min={0}
                    isLoading={isLoading}
                    className="md:w-2/3 shrink-0"
                />
            </div>

            <InlineController
                name="microchipNumber"
                control={control}
                type="text"
                label={t("features.pets.fields.microchipNumber")}
                Icon={Cpu}
                // placeholder={t("features.pets.create.placeholders.microchipNumber")}
                isLoading={isLoading}
            />
        </WizardStepShell>
    );
}
