"use client";

import { Step } from "rhf-stepper";
import { ReactNode } from "react";
import { cn } from "@workspace/ui/lib/utils";

export type StepTransitionDirection = "forward" | "backward";

type StepperStepProps = {
    index: number;
    activeStep: number;
    children: ReactNode;
    isHidden?: boolean;
    direction?: StepTransitionDirection;
    leavingStepIndex?: number | null;
};

export function StepperStep({
    index,
    activeStep,
    children,
    isHidden,
    direction = "forward",
    leavingStepIndex,
}: StepperStepProps) {
    const isLeavingStep = leavingStepIndex === index;
    const isActiveVisibleStep = !isHidden && activeStep === index && !isLeavingStep;
    const shouldRender = isActiveVisibleStep || isLeavingStep;

    if (!shouldRender) {
        return <Step>{null}</Step>;
    }

    const enterSlideClass =
        direction === "forward" ? "slide-in-from-right-8" : "slide-in-from-left-8";
    const exitSlideClass = direction === "forward" ? "slide-out-to-left-8" : "slide-out-to-right-8";

    const animationClass = isActiveVisibleStep
        ? cn("animate-in fade-in duration-300 ease-out", enterSlideClass)
        : cn("animate-out fade-out duration-300 ease-out", exitSlideClass);

    return (
        <Step>
            <div
                className={cn(
                    "w-full h-full",
                    isLeavingStep && "pointer-events-none",
                    animationClass,
                )}
            >
                {children}
            </div>
        </Step>
    );
}
