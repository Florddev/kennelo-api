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
import Link from "next/link";
import Image from "next/image";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { ArrowLeft } from "@solar-icons/react";

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
    const { routes } = useNavigation();
    const t = useTranslations();

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

    const Navigation = ({
        className,
        render,
    }: {
        className?: string;
        render?: React.ComponentProps<typeof StepperNavigation>["render"];
    }) => (
        <div className={className}>
            <StepperNavigation
                steps={steps}
                visibleIndices={visibleIndices}
                labels={labels}
                isLoading={isLoading}
                onBeforeStepChange={handleBeforeStepChange}
                onFinalStepSubmit={() =>
                    onSubmit(form.getValues(), (field, error) => form.setError(field, error))
                }
                render={render}
            />
        </div>
    );

    return (
        <FormProvider {...form}>
            <form id={id} className={cn("space-y-6", className)}>
                <div className="fixed top-0 left-0 w-screen h-screen max-h-[100vh] bg-card z-20">
                    <div className="flex flex-col h-full justify-between overflow-auto">
                        <Stepper>
                            {({ activeStep }) => (
                                <>
                                    <div
                                        className={cn("w-full h-18 px-2 md:px-8 flex items-center")}
                                    >
                                        <div className="hidden md:flex w-full items-center justify-between">
                                            <Link
                                                href={routes.Home()}
                                                className="hidden md:flex relative h-full justify-center items-center font-semibold text-lg"
                                            >
                                                <Image
                                                    className="object-cover max-h-full h-7 w-auto"
                                                    src="/logo_type.svg"
                                                    height={120}
                                                    width={30}
                                                    alt="Kennelo logo"
                                                />
                                            </Link>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="bg-transparent"
                                                >
                                                    {t("common.actions.help")}
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="bg-transparent"
                                                >
                                                    {t("common.actions.save-and-quit")}
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-3 items-center w-full md:hidden">
                                            <Navigation
                                                className="gris"
                                                render={(props) => (
                                                    <Button
                                                        type="button"
                                                        variant="flat"
                                                        size="icon-sm"
                                                        onClick={props.handlePrev}
                                                        disabled={
                                                            props.isFirstVisibleStep ||
                                                            props.isLoading
                                                        }
                                                    >
                                                        <ArrowLeft />
                                                    </Button>
                                                )}
                                            />
                                            <div className="text-center font-medium font-">
                                                <span className="text-primary font-bold">
                                                    {activeStep + 1}{" "}
                                                </span>
                                                <span className="text-muted-foreground/60">
                                                    / {visibleIndices.length}
                                                </span>
                                            </div>
                                            {/* <div className="text-end">
                                                <Button
                                                    type="button"
                                                    variant="link"
                                                    className="px-0"
                                                >
                                                    Quitter
                                                </Button>
                                            </div> */}
                                        </div>
                                    </div>
                                    <div className="w-full bg-background px-2 md:px-8">
                                        {(() => {
                                            const activeVisibleStep =
                                                visibleIndices.indexOf(activeStep);
                                            const groupProgression =
                                                groups && groups.length > 0
                                                    ? calculateGroupProgression(
                                                          groups,
                                                          steps,
                                                          values,
                                                          visibleIndices,
                                                          activeVisibleStep,
                                                      )
                                                    : undefined;

                                            return typeof renderProgress === "function" ? (
                                                renderProgress({
                                                    activeStep,
                                                    stepCount: visibleIndices.length,
                                                    isLoading,
                                                    groups,
                                                    groupProgression,
                                                })
                                            ) : (
                                                <StepperProgress
                                                    activeStep={activeStep}
                                                    stepCount={visibleIndices.length}
                                                    groups={groups}
                                                    groupProgression={groupProgression}
                                                />
                                            );
                                        })()}
                                    </div>

                                    <div className="relative h-full w-full overflow-auto">
                                        <div className="container mx-auto h-full px-4">
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
                                    </div>

                                    <div className="w-full bg-background">
                                        <div className="p-2 md:px-8 md:py-4 bg-card">
                                            <Navigation className="hidden md:block" />

                                            <Navigation
                                                className="md:hidden"
                                                render={(props) => (
                                                    <div className="flex items-center justify-between">
                                                        {!props.isLastVisibleStep ? (
                                                            <Button
                                                                type="button"
                                                                className="rounded-4xl text-base cursor-pointer w-full"
                                                                size="xl"
                                                                onClick={props.handleNext}
                                                                disabled={props.isLoading}
                                                            >
                                                                {labels.next}
                                                            </Button>
                                                        ) : (
                                                            <Button
                                                                type="button"
                                                                className="rounded-4xl text-base cursor-pointer w-full"
                                                                size="xl"
                                                                disabled={props.isLoading}
                                                                onClick={props.handleFinalSubmit}
                                                            >
                                                                {labels.submit}
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}
                                            />
                                        </div>
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
