import { Mars, Venus } from "lucide-react";
import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { cn } from "@workspace/ui/lib/utils";
import { Control, Controller } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InputController } from "@/components/forms/input-controller";

type IdentityStepProps = {
    control: Control<CreatePetInput>;
    isLoading: boolean;
};

export function IdentityStep({ control, isLoading }: IdentityStepProps) {
    const t = useTranslations();

    return (
        <WizardStepShell title={t("features.pets.create.steps.identity.title")}>
            <InputController
                control={control}
                name="name"
                isLoading={isLoading}
                label={t("features.pets.fields.name")}
            />
            <InputController
                control={control}
                name="breed"
                isLoading={isLoading}
                label={t("features.pets.fields.breed")}
            />
            <Field className="gap-1.5">
                <FieldLabel>{t("features.pets.fields.sex")}</FieldLabel>
                <Controller
                    name="sex"
                    control={control}
                    render={({ field }) => (
                        <div className="grid grid-cols-2 gap-2">
                            {(
                                [
                                    { value: "male", Icon: Mars },
                                    { value: "female", Icon: Venus },
                                ] as const
                            ).map(({ value: sexValue, Icon }) => {
                                const isSelected = field.value === sexValue;
                                return (
                                    <button
                                        key={sexValue}
                                        type="button"
                                        disabled={isLoading}
                                        onClick={() =>
                                            field.onChange(isSelected ? "unknown" : sexValue)
                                        }
                                        className={cn(
                                            "flex flex-col items-start justify-between gap-4 rounded-lg border py-4 px-5 text-start transition-all",
                                            isSelected
                                                ? "ring-2 ring-primary bg-primary/5 border-transparent"
                                                : "border-input hover:border-primary/60",
                                        )}
                                    >
                                        <Icon
                                            className={cn(
                                                "size-6",
                                                isSelected
                                                    ? "text-primary"
                                                    : "text-muted-foreground",
                                            )}
                                        />
                                        <ChoiceCardLabel>
                                            {t(`features.pets.sex.${sexValue}`)}
                                        </ChoiceCardLabel>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                />
            </Field>
        </WizardStepShell>
    );
}
