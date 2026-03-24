"use client";

import { Button } from "@workspace/ui/components/button";
import { useFormContext, type FieldValues } from "react-hook-form";
import { useStepper } from "rhf-stepper";
import type { FormStepDefinition, FormStepperLabels } from "./stepper-types";
import type { StepTransitionDirection } from "./stepper-step";

type StepperNavigationProps<TFieldValues extends FieldValues> = {
    steps: FormStepDefinition<TFieldValues>[];
    visibleIndices: number[];
    labels: FormStepperLabels;
    isLoading: boolean;
    formId?: string;
    onBeforeStepChange: (from: number, direction: StepTransitionDirection) => Promise<void>;
};

export function StepperNavigation<TFieldValues extends FieldValues>({
    steps,
    visibleIndices,
    labels,
    isLoading,
    formId,
    onBeforeStepChange,
}: StepperNavigationProps<TFieldValues>) {
    const form = useFormContext<TFieldValues>();
    const { activeStep, jumpTo } = useStepper<TFieldValues>();

    const currentVisibleIndex = visibleIndices.indexOf(activeStep);
    const isFirstVisibleStep = currentVisibleIndex <= 0;
    const isLastVisibleStep =
        currentVisibleIndex >= 0 && currentVisibleIndex === visibleIndices.length - 1;

    const handleNext = async () => {
        const activeStepDefinition = steps[activeStep];

        if (activeStepDefinition?.canProceed) {
            const canProceed = await activeStepDefinition.canProceed(form);
            if (!canProceed) {
                return;
            }
        }

        const fields = activeStepDefinition?.fields ?? [];

        if (fields.length > 0) {
            for (const field of fields) {
                const value = form.getValues(field);
                form.setValue(field, value, {
                    shouldTouch: true,
                    shouldDirty: false,
                    shouldValidate: false,
                });
            }

            const isValid = await form.trigger(fields, { shouldFocus: true });

            if (!isValid) {
                return;
            }
        }

        const nextVisibleStepIndex = visibleIndices[currentVisibleIndex + 1];

        if (nextVisibleStepIndex === undefined) {
            return;
        }

        await onBeforeStepChange(activeStep, "forward");
        await jumpTo(nextVisibleStepIndex);
    };

    const handlePrev = async () => {
        const prevVisibleStepIndex = visibleIndices[currentVisibleIndex - 1];

        if (prevVisibleStepIndex === undefined) {
            return;
        }

        await onBeforeStepChange(activeStep, "backward");
        await jumpTo(prevVisibleStepIndex);
    };

    return (
        <div className="flex items-center justify-between">
            <Button
                type="button"
                variant="link"
                className="px-0"
                onClick={() => {
                    void handlePrev();
                }}
                disabled={isFirstVisibleStep || isLoading}
            >
                {labels.back}
            </Button>

            {!isLastVisibleStep ? (
                <Button
                    type="button"
                    className="rounded-4xl px-8 py-6 text-base"
                    onClick={() => {
                        void handleNext();
                    }}
                    disabled={isLoading}
                >
                    {labels.next}
                </Button>
            ) : (
                <Button
                    type="submit"
                    form={formId}
                    className="rounded-4xl px-8 py-6 text-base"
                    disabled={isLoading}
                >
                    {labels.submit}
                </Button>
            )}
        </div>
    );
}
