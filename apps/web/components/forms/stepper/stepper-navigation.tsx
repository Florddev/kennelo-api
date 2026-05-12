"use client";

import type { ReactNode } from "react";
import { Button } from "@workspace/ui/components/button";
import { useFormContext, type FieldValues } from "react-hook-form";
import { useStepper } from "rhf-stepper";
import type { FormStepDefinition, FormStepperLabels } from "./stepper-types";
import type { StepTransitionDirection } from "./stepper-step";

export type StepperNavigationRenderProps = {
    isFirstVisibleStep: boolean;
    isLastVisibleStep: boolean;
    isLoading: boolean;
    handleNext: () => Promise<void>;
    handlePrev: () => Promise<void>;
    handleFinalSubmit: () => Promise<void>;
    labels: FormStepperLabels;
};

type StepperNavigationProps<TFieldValues extends FieldValues> = {
    steps: FormStepDefinition<TFieldValues>[];
    visibleIndices: number[];
    labels: FormStepperLabels;
    isLoading: boolean;
    formId?: string;
    onBeforeStepChange: (from: number, direction: StepTransitionDirection) => Promise<void>;
    onFinalStepSubmit: () => Promise<void> | void;
    onFirstStepBack?: () => void;
    render?: (props: StepperNavigationRenderProps) => ReactNode;
};

export function StepperNavigation<TFieldValues extends FieldValues>({
    steps,
    visibleIndices,
    labels,
    isLoading,
    onBeforeStepChange,
    onFinalStepSubmit,
    onFirstStepBack,
    render,
}: StepperNavigationProps<TFieldValues>) {
    const form = useFormContext<TFieldValues>();
    const { activeStep, jumpTo } = useStepper<TFieldValues>();

    const currentVisibleIndex = visibleIndices.indexOf(activeStep);
    const isFirstVisibleStep = currentVisibleIndex <= 0;
    const isLastVisibleStep =
        currentVisibleIndex >= 0 && currentVisibleIndex === visibleIndices.length - 1;

    const handleNext = async () => {
        const activeStepDefinition = steps[activeStep];

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

        if (activeStepDefinition?.canProceed) {
            const canProceed = await activeStepDefinition.canProceed(form);
            if (!canProceed) {
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

    const handleFinalSubmit = async () => {
        const activeStepDefinition = steps[activeStep];
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

        if (activeStepDefinition?.canProceed) {
            const canProceed = await activeStepDefinition.canProceed(form);
            if (!canProceed) {
                return;
            }
        }

        await onFinalStepSubmit();
    };

    const handlePrev = async () => {
        if (isFirstVisibleStep) {
            onFirstStepBack?.();
            return;
        }

        const prevVisibleStepIndex = visibleIndices[currentVisibleIndex - 1];

        if (prevVisibleStepIndex === undefined) {
            return;
        }

        await onBeforeStepChange(activeStep, "backward");
        await jumpTo(prevVisibleStepIndex);
    };

    if (render) {
        return render({
            isFirstVisibleStep,
            isLastVisibleStep,
            isLoading,
            handleNext,
            handlePrev,
            handleFinalSubmit,
            labels,
        });
    }

    return (
        <div className="flex items-center justify-between">
            <Button
                type="button"
                variant="link"
                className="px-0 text-base cursor-pointer"
                size="lg"
                onClick={() => {
                    void handlePrev();
                }}
                disabled={isLoading}
            >
                {labels.back}
            </Button>

            {!isLastVisibleStep ? (
                <Button
                    type="button"
                    className="rounded-4xl text-base cursor-pointer"
                    size="lg"
                    onClick={() => {
                        void handleNext();
                    }}
                    disabled={isLoading}
                >
                    {labels.next}
                </Button>
            ) : (
                <Button
                    type="button"
                    className="rounded-4xl text-base cursor-pointer"
                    size="lg"
                    disabled={isLoading}
                    onClick={() => {
                        void handleFinalSubmit();
                    }}
                >
                    {labels.submit}
                </Button>
            )}
        </div>
    );
}
