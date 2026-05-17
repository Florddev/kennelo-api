"use client";

import { cn } from "@workspace/ui/lib/utils";
import { ReactNode } from "react";

export function WizardStepShell({
    title,
    subtitle,
    children,
    className,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className="flex flex-col gap-6 md:gap-8 mx-auto max-w-2xl py-6 sm:py-12 w-full h-fit justify-center">
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl md:text-3xl font-semibold tracking-tight">{title}</h1>
                {subtitle && <p className="text-lg text-muted-foreground">{subtitle}</p>}
            </div>
            <div className={cn("flex flex-col gap-2 md:gap-4", className)}>{children}</div>
        </div>
    );
}
