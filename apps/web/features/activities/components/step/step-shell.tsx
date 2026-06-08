"use client";

import { ReactNode } from "react";
import { WizardStepShell } from "@/components/forms/stepper/wizard-step-shell";

type StepShellProps = {
    title: string;
    subtitle?: string;
    children: ReactNode;
};

export function StepShell({ title, subtitle, children }: StepShellProps) {
    return (
        <WizardStepShell title={title} subtitle={subtitle}>
            {children}
        </WizardStepShell>
    );
}
