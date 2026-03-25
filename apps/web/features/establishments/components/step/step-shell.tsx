"use client";

import { ReactNode } from "react";

type StepShellProps = {
    title: string;
    subtitle?: string;
    children: ReactNode;
};

export function StepShell({ title, subtitle, children }: StepShellProps) {
    return (
        <div className="flex flex-col gap-8 mx-auto max-w-2xl py-12 w-full min-h-full h-fit justify-center">
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl md:text-3xl font-semibold tracking-tight">{title}</h1>
                {subtitle && <p className="text-lg text-muted-foreground">{subtitle}</p>}
            </div>
            <div className="flex flex-col gap-5">{children}</div>
        </div>
    );
}
