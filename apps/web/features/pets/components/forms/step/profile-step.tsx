import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { ChoiceCards } from "@workspace/ui/components/choice-cards";
import { Field, FieldDescription, FieldLabel } from "@workspace/ui/components/field";
import { Control, Controller, useWatch } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InputController } from "@/components/forms/input-controller";

type ProfileStepProps = {
    control: Control<CreatePetInput>;
    isLoading: boolean;
};

function booleanToChoice(value: unknown): "yes" | "no" | null {
    if (value === true) return "yes";
    if (value === false) return "no";
    return null;
}

export function ProfileStep({ control, isLoading }: ProfileStepProps) {
    const t = useTranslations();
    const hasMicrochip = useWatch({ control, name: "hasMicrochip" });

    return (
        <WizardStepShell title={t("features.pets.create.steps.profile.title")}>
            <Field className="gap-1.5">
                <FieldLabel>{t("features.pets.fields.sterilized")}</FieldLabel>
                <Controller
                    name="isSterilized"
                    control={control}
                    render={({ field }) => (
                        <ChoiceCards
                            mode="single"
                            value={booleanToChoice(field.value)}
                            onValueChange={(value) => field.onChange(value === "yes")}
                            optionsClassName="md:grid-cols-2"
                            options={[
                                { value: "yes", label: t("common.actions.yes") },
                                { value: "no", label: t("common.actions.no") },
                            ]}
                            layout="grid"
                        />
                    )}
                />
            </Field>
            <Field className="gap-1.5">
                <FieldLabel>{t("features.pets.badges.microchipped")}</FieldLabel>
                <Controller
                    name="hasMicrochip"
                    control={control}
                    render={({ field }) => (
                        <ChoiceCards
                            mode="single"
                            value={field.value ? "yes" : "no"}
                            onValueChange={(value) => field.onChange(value === "yes")}
                            optionsClassName="md:grid-cols-2"
                            options={[
                                { value: "yes", label: t("common.actions.yes") },
                                { value: "no", label: t("common.actions.no") },
                            ]}
                            layout="grid"
                        />
                    )}
                />
            </Field>
            {hasMicrochip && (
                <Field className="gap-1.5">
                    <InputController
                        control={control}
                        name="microchipNumber"
                        isLoading={isLoading}
                        label={t("features.pets.fields.microchipNumber")}
                        placeholder={t("features.pets.create.placeholders.microchipNumber")}
                    />
                    <FieldDescription>
                        {t("features.pets.create.steps.profile.microchipHint")}
                    </FieldDescription>
                </Field>
            )}
        </WizardStepShell>
    );
}
