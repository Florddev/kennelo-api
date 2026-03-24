"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { FormProvider, useForm, useWatch, type FieldValues } from "react-hook-form";
import { Stepper } from "rhf-stepper";
import { cn } from "@workspace/ui/lib/utils";
import { useMemo, useRef, useState } from "react";
import { StepperNavigation } from "./stepper-navigation";
import { StepperProgress } from "./stepper-progress";
import { StepperStep, type StepTransitionDirection } from "./stepper-step";
import type { FormStepperProps, FormStepDefinition } from "./stepper-types";
import { Button } from "@workspace/ui/components/button";

const STEP_EXIT_DURATION_MS = 50;

const calculateGroupProgression = <TFieldValues extends FieldValues>(
    groups: string[],
    steps: FormStepDefinition<TFieldValues>[],
    values: TFieldValues,
    visibleIndices: number[],
    activeVisibleStep: number,
): Record<string, number> => {
    const progressionByGroup: Record<string, number> = {};

    for (const groupId of groups) {
        const stepsInGroup = steps
            .map((step, idx) => ({ step, idx }))
            .filter(
                ({ step }) =>
                    step.groupId === groupId && (step.isVisible ? step.isVisible(values) : true),
            );

        const visibleStepsInGroup = stepsInGroup.filter(({ idx }) => visibleIndices.includes(idx));

        if (visibleStepsInGroup.length === 0) {
            progressionByGroup[groupId] = 0;
            continue;
        }

        const firstStepInGroup = visibleStepsInGroup[0];
        const lastStepInGroup = visibleStepsInGroup[visibleStepsInGroup.length - 1];

        if (!firstStepInGroup || !lastStepInGroup) {
            progressionByGroup[groupId] = 0;
            continue;
        }

        const firstGroupStepPosition = visibleIndices.indexOf(firstStepInGroup.idx);
        const lastGroupStepPosition = visibleIndices.indexOf(lastStepInGroup.idx);

        if (firstGroupStepPosition < 0 || lastGroupStepPosition < 0 || activeVisibleStep < 0) {
            progressionByGroup[groupId] = 0;
        } else if (activeVisibleStep < firstGroupStepPosition) {
            progressionByGroup[groupId] = 0;
        } else if (activeVisibleStep >= lastGroupStepPosition) {
            progressionByGroup[groupId] = 1;
        } else {
            const groupCompletion =
                (activeVisibleStep - firstGroupStepPosition + 1) / visibleStepsInGroup.length;
            progressionByGroup[groupId] = Math.min(groupCompletion, 1);
        }
    }

    return progressionByGroup;
};

export function FormStepper<TFieldValues extends FieldValues>({
    schema,
    defaultValues,
    steps,
    labels,
    onSubmit,
    isLoading = false,
    formId,
    className,
    groups,
    renderProgress,
}: FormStepperProps<TFieldValues>) {
    const form = useForm<TFieldValues>({
        resolver: zodResolver(schema as never),
        defaultValues,
    });
    const [direction, setDirection] = useState<StepTransitionDirection>("forward");
    const [leavingStepIndex, setLeavingStepIndex] = useState<number | null>(null);
    const leavingTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const values = useWatch({ control: form.control }) as TFieldValues;

    const visibleIndices = useMemo(
        () =>
            steps
                .map((step, idx) => ({ step, idx }))
                .filter(({ step }) => (step.isVisible ? step.isVisible(values) : true))
                .map(({ idx }) => idx),
        [steps, values],
    );

    const id = formId ?? "form-stepper";

    const handleBeforeStepChange = async (from: number, nextDirection: StepTransitionDirection) => {
        setDirection(nextDirection);
        setLeavingStepIndex(from);

        if (leavingTimerRef.current) {
            clearTimeout(leavingTimerRef.current);
        }

        await new Promise<void>((resolve) => {
            leavingTimerRef.current = setTimeout(() => {
                setLeavingStepIndex(null);
                leavingTimerRef.current = null;
                resolve();
            }, STEP_EXIT_DURATION_MS);
        });
    };

    return (
        <FormProvider {...form}>
            <form
                id={id}
                onSubmit={form.handleSubmit(onSubmit)}
                className={cn("space-y-6", className)}
            >
                <div className="fixed top-0 left-0 w-screen h-screen bg-background z-100">
                    <div className="flex flex-col h-full justify-between">
                        <div className="fixed w-full h-16 px-8 flex items-center justify-between">
                            <div>logo</div>
                            <div className="flex gap-2">
                                <Button variant="outline" className="bg-transparent">
                                    Des question ?
                                </Button>
                                <Button variant="outline" className="bg-transparent">
                                    Enregistrer et quitter
                                </Button>
                            </div>
                        </div>
                        <Stepper>
                            {({ activeStep }) => (
                                <>
                                    <div className="container mx-auto relative h-full w-full overflow-auto">
                                        {steps.map((step, index) => {
                                            const isVisible = visibleIndices.includes(index);

                                            return (
                                                <StepperStep
                                                    key={step.id}
                                                    index={index}
                                                    activeStep={activeStep}
                                                    isHidden={!isVisible}
                                                    direction={direction}
                                                    leavingStepIndex={leavingStepIndex}
                                                >
                                                    {isVisible &&
                                                        step.component({
                                                            control: form.control,
                                                            isLoading,
                                                        })}
                                                </StepperStep>
                                            );
                                        })}
                                    </div>

                                    {(() => {
                                        const activeVisibleStep =
                                            visibleIndices.indexOf(activeStep);

                                        return typeof renderProgress === "function" ? (
                                            renderProgress({
                                                activeStep,
                                                stepCount: visibleIndices.length,
                                                isLoading,
                                                groups,
                                                groupProgression:
                                                    groups && groups.length > 0
                                                        ? calculateGroupProgression(
                                                              groups,
                                                              steps,
                                                              values,
                                                              visibleIndices,
                                                              activeVisibleStep,
                                                          )
                                                        : undefined,
                                            })
                                        ) : (
                                            <StepperProgress
                                                activeStep={activeStep}
                                                stepCount={visibleIndices.length}
                                                groups={groups}
                                                groupProgression={
                                                    groups && groups.length > 0
                                                        ? calculateGroupProgression(
                                                              groups,
                                                              steps,
                                                              values,
                                                              visibleIndices,
                                                              activeVisibleStep,
                                                          )
                                                        : undefined
                                                }
                                            />
                                        );
                                    })()}

                                    <div className="px-8 py-4">
                                        <StepperNavigation
                                            steps={steps}
                                            visibleIndices={visibleIndices}
                                            labels={labels}
                                            isLoading={isLoading}
                                            formId={id}
                                            onBeforeStepChange={handleBeforeStepChange}
                                        />
                                    </div>
                                </>
                            )}
                        </Stepper>
                    </div>
                </div>
            </form>
        </FormProvider>
    );
}
