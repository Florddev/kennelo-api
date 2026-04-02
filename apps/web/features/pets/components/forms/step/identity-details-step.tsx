import { Calendar as CalendarIcon } from "lucide-react";
import { useState } from "react";
import { useTranslations } from "next-intl";
import { type CreatePetInput } from "@workspace/modules/pets";
import { Button } from "@workspace/ui/components/button";
import { Calendar } from "@workspace/ui/components/calendar";
import { Field, FieldLabel } from "@workspace/ui/components/field";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { Control, useController } from "react-hook-form";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";
import { InputController } from "@/components/forms/input-controller";
import { TextareaController } from "@/components/forms/textarea-controller";

function computeApproximateBirthDate(years: number, months: number): string {
    const date = new Date();
    date.setMonth(date.getMonth() - (years * 12 + months));
    date.setDate(15);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-15`;
}

type IdentityDetailsStepProps = {
    control: Control<CreatePetInput>;
    isLoading: boolean;
};

export function IdentityDetailsStep({ control, isLoading }: IdentityDetailsStepProps) {
    const t = useTranslations();
    const [ageMode, setAgeMode] = useState<"date" | "approximate">("date");
    const [approxYears, setApproxYears] = useState<number | "">("");
    const [approxMonths, setApproxMonths] = useState<number | "">("");
    const { field: birthDateField } = useController({ control, name: "birthDate" });

    const selectedDate =
        ageMode === "date" && birthDateField.value
            ? new Date(`${birthDateField.value}T00:00:00`)
            : undefined;

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
        <WizardStepShell title={t("features.pets.create.steps.identityDetails.title")}>
            <div className="grid sm:grid-cols-2 gap-6">
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
                                {t(
                                    "features.pets.create.steps.identityDetails.switchToApproximate",
                                )}
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={() => handleModeChange("date")}
                                className="text-xs text-muted-foreground hover:text-foreground transition-colors text-start underline-offset-2 hover:underline w-fit"
                            >
                                {t("features.pets.create.steps.identityDetails.switchToDate")}
                            </button>
                        )}
                    </div>
                    {ageMode === "date" ? (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={isLoading}
                                    className="justify-between rounded-2xl bg-card w-full py-6"
                                >
                                    {selectedDate &&
                                        new Intl.DateTimeFormat(undefined, {
                                            year: "numeric",
                                            month: "long",
                                            day: "numeric",
                                        }).format(selectedDate)}
                                    <CalendarIcon className="size-4 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-fit p-0">
                                <Calendar
                                    mode="single"
                                    captionLayout="dropdown"
                                    startMonth={new Date(1950, 0)}
                                    endMonth={new Date()}
                                    selected={selectedDate}
                                    onSelect={(date) => {
                                        if (!date) {
                                            birthDateField.onChange("");
                                            return;
                                        }
                                        const formatted = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
                                        birthDateField.onChange(formatted);
                                    }}
                                    disabled={{ after: new Date() }}
                                />
                            </PopoverContent>
                        </Popover>
                    ) : (
                        <div className="flex gap-2">
                            <Field className="gap-1.5 flex-1">
                                <div className="bg-card py-3.5 px-3 rounded-2xl border">
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
                                <div className="bg-card py-3.5 px-3 rounded-2xl border">
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
                <div className="col-span-1">
                    <InputController
                        control={control}
                        name="adoptionDate"
                        type="date"
                        isLoading={isLoading}
                        label={t("features.pets.fields.adoptionDate")}
                    />
                </div>
            </div>
            <InputController
                control={control}
                name="weight"
                isLoading={isLoading}
                type="number"
                label={t("features.pets.fields.weight")}
            />
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
