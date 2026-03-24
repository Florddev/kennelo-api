import { ReactNode } from "react";
import { Control, DefaultValues, FieldPath, FieldValues, UseFormReturn } from "react-hook-form";

export type FormStepComponentProps<TFieldValues extends FieldValues> = {
    control: Control<TFieldValues>;
    isLoading: boolean;
};

export type FormStepDefinition<TFieldValues extends FieldValues> = {
    id: string;
    fields: FieldPath<TFieldValues>[];
    component: (props: FormStepComponentProps<TFieldValues>) => ReactNode;
    isVisible?: (values: TFieldValues) => boolean;
    canProceed?: (form: UseFormReturn<TFieldValues>) => boolean | Promise<boolean>;
    groupId?: string;
};

export type FormStepperLabels = {
    back: string;
    next: string;
    submit: string;
};

export type FormStepperProps<TFieldValues extends FieldValues> = {
    schema: unknown;
    defaultValues: DefaultValues<TFieldValues>;
    steps: FormStepDefinition<TFieldValues>[];
    labels: FormStepperLabels;
    onSubmit: (values: TFieldValues) => Promise<void> | void;
    isLoading?: boolean;
    formId?: string;
    className?: string;
    groups?: string[];
    renderProgress?: (props: {
        activeStep: number;
        stepCount: number;
        isLoading: boolean;
        groups?: string[];
        groupProgression?: Record<string, number>;
    }) => ReactNode;
};
