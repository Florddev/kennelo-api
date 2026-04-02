"use client";

import { ReactNode } from "react";
import {
    ChoiceCardContainer,
    ChoiceCardContent,
    ChoiceCardDescription,
    ChoiceCardLabel,
    ChoiceCards,
    type ChoiceCardOption,
} from "@workspace/ui/components/choice-cards";
import { cn } from "@workspace/ui/lib/utils";
import { WizardStepShell } from "./wizard-step-shell";

export type SingleChoiceCardStepOption<TValue extends string> = {
    value: TValue;
    label: string;
    description?: string;
    visual?: ReactNode;
    disabled?: boolean;
    className?: string;
};

type SingleChoiceCardsStepProps<TValue extends string> = {
    title: string;
    subtitle?: string;
    value: TValue | null;
    onChange: (value: TValue) => void;
    options: SingleChoiceCardStepOption<TValue>[];
    error?: string;
    optionsClassName?: string;
};

export function SingleChoiceCardsStep<TValue extends string>({
    title,
    subtitle,
    value,
    onChange,
    options,
    error,
    optionsClassName,
}: SingleChoiceCardsStepProps<TValue>) {
    const normalizedOptions: ChoiceCardOption<TValue>[] = options.map((option) => ({
        value: option.value,
        label: option.label,
        description: option.description,
        disabled: option.disabled,
        className: option.className,
    }));

    const visualsByValue = Object.fromEntries(
        options.map((option) => [option.value, option.visual]),
    ) as Record<TValue, ReactNode>;

    return (
        <WizardStepShell title={title} subtitle={subtitle}>
            <ChoiceCards
                mode="single"
                options={normalizedOptions}
                value={value}
                optionsClassName={cn("md:grid-cols-3", optionsClassName)}
                render={(option, isSelected) => (
                    <ChoiceCardContainer
                        isSelected={isSelected}
                        disabled={option.disabled}
                        className={cn("flex-col h-full justify-between", option.className)}
                    >
                        {visualsByValue[option.value]}
                        <ChoiceCardContent>
                            <ChoiceCardLabel>{option.label}</ChoiceCardLabel>
                            {option.description && (
                                <ChoiceCardDescription>{option.description}</ChoiceCardDescription>
                            )}
                        </ChoiceCardContent>
                    </ChoiceCardContainer>
                )}
                onValueChange={onChange}
                error={error}
                layout="grid"
            />
        </WizardStepShell>
    );
}
