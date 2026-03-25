"use client";

import { cn } from "@workspace/ui/lib/utils";

type StepperProgressProps = {
    activeStep: number;
    stepCount: number;
    className?: string;
    groups?: string[];
    groupProgression?: Record<string, number>;
};

export function StepperProgress({
    activeStep,
    stepCount,
    className,
    groups,
    groupProgression,
}: StepperProgressProps) {
    const hasGroups = groups && groups.length > 0 && groupProgression;

    if (stepCount <= 1) {
        return null;
    }

    if (hasGroups) {
        return (
            <div className={cn("flex gap-2", className)}>
                {groups!.map((groupId) => {
                    const progression = groupProgression![groupId] ?? 0;

                    return (
                        <div
                            key={groupId}
                            className="flex-1 h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                className={cn(
                                    "h-full bg-primary transition-all duration-300 ease-out",
                                )}
                                style={{ width: `${progression * 100}%` }}
                            />
                        </div>
                    );
                })}
            </div>
        );
    }

    return (
        <div className={cn("flex gap-1.5", className)}>
            {Array.from({ length: stepCount }).map((_, index) => (
                <div key={index} className="flex-1 h-1 rounded-full overflow-hidden bg-muted">
                    <div
                        className={cn(
                            "h-full rounded-full bg-primary transition-all duration-300",
                            index <= activeStep ? "w-full" : "w-0",
                        )}
                    />
                </div>
            ))}
        </div>
    );
}
