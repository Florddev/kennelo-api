import { useState } from "react";
import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { ChoiceCardLabel } from "@workspace/ui/components/choice-cards";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { cn } from "@workspace/ui/lib/utils";
import { Control, Controller, useController } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";
import { Men, Women } from "@solar-icons/react";

function computeApproximateBirthDate(years: number, months: number): string {
    const date = new Date();
    date.setMonth(date.getMonth() - (years * 12 + months));
    date.setDate(15);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-15`;
}

type IdentityStepProps = {
    control: Control<CreatePetInput>;
    isLoading: boolean;
};

export function IdentityStep({ control, isLoading }: IdentityStepProps) {
    const t = useTranslations();
    const [ageMode, setAgeMode] = useState<"date" | "approximate">("date");
    const [approxYears, setApproxYears] = useState<number | "">("");
    const [approxMonths, setApproxMonths] = useState<number | "">("");
    const { field: birthDateField } = useController({ control, name: "birthDate" });

    function handleModeChange(mode: "date" | "approximate") {
        setAgeMode(mode);
        birthDateField.onChange("");
        setApproxYears("");
        setApproxMonths("");
    }

    function handleApproxChange(years: number | "", months: number | "") {
        const y = typeof years === "number" ? years : 0;
        const m = typeof months === "number" ? months : 0;
        if (y > 0 || m > 0) {
            birthDateField.onChange(computeApproximateBirthDate(y, m));
        } else {
            birthDateField.onChange("");
        }
    }

    return (
        <WizardStepShell title={t("features.pets.create.steps.identity.title")}>
            <InputController
                control={control}
                name="name"
                isLoading={isLoading}
                label={t("features.pets.fields.name")}
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
                                    { value: "male", Icon: Men },
                                    { value: "female", Icon: Women },
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
                                            "flex items-center gap-2 md:items-start md:justify-between md:flex-col md:gap-4 rounded-sm border px-3.5 py-3 md:px-5 text-start transition-all",
                                            isSelected
                                                ? "ring-2 ring-primary bg-primary/5 border-transparent"
                                                : "border-input hover:border-primary/60",
                                        )}
                                    >
                                        <Icon
                                            className={cn(
                                                "size-4 md:size-6 text-primary",
                                                // isSelected
                                                //     ? "text-primary"
                                                //     : "text-muted-foreground",
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
            <div className="flex gap-2">
                <InputController
                    control={control}
                    name="breed"
                    isLoading={isLoading}
                    label={t("features.pets.fields.breed")}
                />
                <InputController
                    control={control}
                    name="weight"
                    isLoading={isLoading}
                    type="number"
                    label={t("features.pets.fields.weight")}
                />
            </div>
            <div className="grid /*sm:grid-cols-2*/ gap-6">
                <Field className="gap-1.5 col-span-1">
                    <div className="flex justify-between items-center">
                        <FieldLabel>
                            {ageMode === "date"
                                ? t("features.pets.fields.birthDate")
                                : t(
                                      "features.pets.create.steps.identityDetails.ageModeApproximate",
                                  )}
                        </FieldLabel>
                        {ageMode === "date" ? (
                            <button
                                type="button"
                                onClick={() => handleModeChange("approximate")}
                                className="text-xs text-muted-foreground hover:text-foreground transition-colors text-start underline-offset-2 hover:underline w-fit"
                            >
                                {t("features.pets.create.steps.identityDetails.ageModeApproximate")}
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={() => handleModeChange("date")}
                                className="text-xs text-muted-foreground hover:text-foreground transition-colors text-start underline-offset-2 hover:underline w-fit"
                            >
                                {t("features.pets.fields.birthDate")}
                            </button>
                        )}
                    </div>
                    {ageMode === "date" ? (
                        <InputController
                            control={control}
                            name="birthDate"
                            isLoading={isLoading}
                            type="date"
                        />
                    ) : (
                        <div className="flex gap-2">
                            <Field className="gap-1.5 flex-1">
                                <div className="bg-card py-2.5 md:py-3.5 px-3 rounded-2xl border">
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        disabled={isLoading}
                                        placeholder={t("features.pets.fields.ageYears")}
                                        value={approxYears}
                                        className="w-full bg-transparent outline-none text-sm"
                                        onChange={(e) => {
                                            const val =
                                                e.target.value === "" ? "" : Number(e.target.value);
                                            setApproxYears(val);
                                            handleApproxChange(val, approxMonths);
                                        }}
                                    />
                                </div>
                            </Field>
                            <Field className="gap-1.5 flex-1">
                                <div className="bg-card py-2.5 md:py-3.5 px-3 rounded-2xl border">
                                    <input
                                        type="number"
                                        min="0"
                                        max="11"
                                        disabled={isLoading}
                                        placeholder={t("features.pets.fields.ageMonths")}
                                        value={approxMonths}
                                        className="w-full bg-transparent outline-none text-sm"
                                        onChange={(e) => {
                                            const val =
                                                e.target.value === "" ? "" : Number(e.target.value);
                                            setApproxMonths(val);
                                            handleApproxChange(approxYears, val);
                                        }}
                                    />
                                </div>
                            </Field>
                        </div>
                    )}
                </Field>
                {/* <div className="col-span-1">
                    <InputController
                        control={control}
                        name="adoptionDate"
                        type="date"
                        isLoading={isLoading}
                        label={t("features.pets.fields.adoptionDate")}
                    />
                </div> */}
            </div>
            <TextareaController
                control={control}
                name="about"
                isLoading={isLoading}
                rows={4}
                label={t("features.pets.fields.about")}
                placeholder={t("features.pets.create.placeholders.about")}
            />
        </WizardStepShell>
    );
}
