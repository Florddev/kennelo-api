"use client";

import { useState, useRef, useCallback, useMemo } from "react";
import Image from "next/image";
import Link from "next/link";
import { useForm, type FieldPath } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { CheckCircle, PawPrint } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import {
    createEstablishmentSchema,
    createEstablishment,
    type CreateEstablishmentInput,
} from "@workspace/modules/establishments";
import { useAsyncState } from "@/hooks/use-async-state";
import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth";
import { useStepper, steps, type StepId } from "../stepper";
import { WelcomeStep } from "./steps/welcome-step";
import { HostTypeStep } from "./steps/host-type-step";
import { EstablishmentTypeStep, type EstablishmentType } from "./steps/establishment-type-step";
import { EstablishmentInfoStep } from "./steps/establishment-info-step";
import { ContactDetailsStep } from "./steps/contact-details-step";
import { AddressStep } from "./steps/address-step";
import { BusinessInfoStep } from "./steps/business-info-step";
import { ReviewStep } from "./steps/review-step";

type HostType = "professional" | "individual";

const ADDRESS_LINE1 = "address.line1";
const ADDRESS_CITY = "address.city";
const ADDRESS_POSTAL_CODE = "address.postalCode";
const ADDRESS_COUNTRY = "address.country";

const STEP_FIELDS: Record<string, string[]> = {
    "establishment-info": ["name", "description"],
    "contact-details": ["phone", "email", "website"],
    address: [
        ADDRESS_LINE1,
        "address.line2",
        ADDRESS_CITY,
        ADDRESS_POSTAL_CODE,
        "address.region",
        ADDRESS_COUNTRY,
    ],
    "business-info": ["siret"],
};

const API_TO_FORM_FIELD: Record<string, string> = {
    name: "name",
    description: "description",
    phone: "phone",
    email: "email",
    website: "website",
    siret: "siret",
    "address.line1": "address.line1",
    "address.line2": "address.line2",
    "address.city": "address.city",
    "address.postal_code": "address.postalCode",
    "address.region": "address.region",
    "address.country": "address.country",
};

function findStepForField(fieldName: string): string | undefined {
    for (const [stepId, fields] of Object.entries(STEP_FIELDS)) {
        if (fields.includes(fieldName)) return stepId;
    }
    return undefined;
}

function getActiveSteps(hostType: HostType | null): StepId[] {
    if (!hostType) {
        return ["welcome", "host-type"];
    }

    if (hostType === "individual") {
        return ["welcome", "host-type", "contact-details", "address", "review"];
    }

    return [
        "welcome",
        "host-type",
        "establishment-type",
        "establishment-info",
        "contact-details",
        "address",
        "business-info",
        "review",
    ];
}

function getNextStep(currentId: StepId, activeSteps: StepId[]): StepId | undefined {
    const currentIndex = activeSteps.indexOf(currentId);
    if (currentIndex === -1 || currentIndex >= activeSteps.length - 1) return undefined;
    return activeSteps[currentIndex + 1];
}

function getPrevStep(currentId: StepId, activeSteps: StepId[]): StepId | undefined {
    const currentIndex = activeSteps.indexOf(currentId);
    if (currentIndex <= 0) return undefined;
    return activeSteps[currentIndex - 1];
}

export function BecomeHostStepper() {
    const t = useTranslations();
    const { routes, router } = useNavigation();
    const { execute, isLoading } = useAsyncState();
    const { user, refreshUser } = useAuth();
    const stepper = useStepper();
    const [isSuccess, setIsSuccess] = useState(false);
    const [submitError, setSubmitError] = useState<string | undefined>();
    const [hostType, setHostType] = useState<HostType | null>(null);
    const [establishmentType, setEstablishmentType] = useState<EstablishmentType | null>(null);
    const [selectionError, setSelectionError] = useState<string | undefined>();
    const directionRef = useRef<"forward" | "backward">("forward");
    const isJumpRef = useRef(false);
    const prevIndexRef = useRef(stepper.state.current.index);
    const submitIntentRef = useRef(false);

    const activeSteps = useMemo(() => getActiveSteps(hostType), [hostType]);

    const currentIndex = stepper.state.current.index;
    if (prevIndexRef.current !== currentIndex) {
        const diff = Math.abs(currentIndex - prevIndexRef.current);
        isJumpRef.current = diff > 1;
        directionRef.current = currentIndex > prevIndexRef.current ? "forward" : "backward";
        prevIndexRef.current = currentIndex;
    }

    const { handleSubmit, control, trigger, setError, watch, setValue } =
        useForm<CreateEstablishmentInput>({
            resolver: zodResolver(createEstablishmentSchema),
            defaultValues: {
                name: "",
                description: "",
                phone: "",
                email: "",
                website: "",
                siret: "",
                address: {
                    line1: "",
                    line2: "",
                    city: "",
                    postalCode: "",
                    region: "",
                    country: "",
                },
            },
        });

    const handleHostTypeChange = useCallback(
        (type: HostType) => {
            setHostType(type);
            setSelectionError(undefined);
            if (type === "individual" && user) {
                setValue("name", user.getFullName());
                if (user.phone) setValue("phone", user.phone);
                if (user.email) setValue("email", user.email);
            } else {
                setValue("name", "");
                setValue("phone", "");
                setValue("email", "");
            }
        },
        [user, setValue],
    );

    const currentStepId = stepper.state.current.data.id as StepId;

    const validateRequiredFields = (stepId: StepId): boolean => {
        const requiredByStep: Record<string, string[]> = {
            "establishment-info": ["name"],
            "contact-details": ["phone", "email"],
            address: [ADDRESS_LINE1, ADDRESS_CITY, ADDRESS_POSTAL_CODE, ADDRESS_COUNTRY],
        };

        const required = requiredByStep[stepId];
        if (!required) return true;

        const values = watch();
        let valid = true;
        const requiredMessage = t("errors.validation.required");
        for (const field of required) {
            const parts = field.split(".");
            let fieldValue: unknown = values;
            for (const part of parts) {
                fieldValue = (fieldValue as Record<string, unknown>)?.[part];
            }
            if (!fieldValue || (typeof fieldValue === "string" && fieldValue.trim() === "")) {
                setError(field as FieldPath<CreateEstablishmentInput>, {
                    type: "required",
                    message: requiredMessage,
                });
                valid = false;
            }
        }
        return valid;
    };

    const handleNext = async () => {
        if (currentStepId === "host-type") {
            if (!hostType) {
                setSelectionError(t("features.become-host.steps.hostType.error"));
                return;
            }
            setSelectionError(undefined);
        }

        if (currentStepId === "establishment-type") {
            if (!establishmentType) {
                setSelectionError(t("features.become-host.steps.establishmentType.error"));
                return;
            }
            setSelectionError(undefined);
        }

        if (!validateRequiredFields(currentStepId)) return;

        const fields = STEP_FIELDS[currentStepId];
        if (fields) {
            const valid = await trigger(fields as (keyof CreateEstablishmentInput)[]);
            if (!valid) return;
        }

        setSubmitError(undefined);
        setSelectionError(undefined);
        const nextStep = getNextStep(currentStepId, activeSteps);
        if (nextStep) {
            stepper.navigation.goTo(nextStep);
        }
    };

    const handleBack = () => {
        setSubmitError(undefined);
        const prevStep = getPrevStep(currentStepId, activeSteps);
        if (prevStep) {
            stepper.navigation.goTo(prevStep);
        }
    };

    const handleGoTo = (stepId: string) => {
        setSubmitError(undefined);
        if (activeSteps.includes(stepId as StepId)) {
            stepper.navigation.goTo(stepId as (typeof steps)[number]["id"]);
        }
    };

    const onSubmit = useCallback(
        async (data: CreateEstablishmentInput) => {
            setSubmitError(undefined);
            let navigatedToStep = false;

            const result = await execute(() => createEstablishment(data), {
                displayError: false,
                onFailure: (errorMessage) => {
                    if (!navigatedToStep) {
                        setSubmitError(errorMessage);
                    }
                },
                setFieldError: (field: string, error: { message: string }) => {
                    const formField = API_TO_FORM_FIELD[field] || field;
                    setError(formField as FieldPath<CreateEstablishmentInput>, error);

                    if (!navigatedToStep) {
                        const targetStep = findStepForField(formField);
                        if (targetStep && activeSteps.includes(targetStep as StepId)) {
                            navigatedToStep = true;
                            stepper.navigation.goTo(targetStep as (typeof steps)[number]["id"]);
                        }
                    }
                },
            });

            if (result) {
                await refreshUser();
                setIsSuccess(true);
            }
        },
        [execute, setError, stepper.navigation, refreshUser, activeSteps],
    );

    const onFormSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            if (!submitIntentRef.current) return;
            submitIntentRef.current = false;
            handleSubmit(onSubmit)(e);
        },
        [handleSubmit, onSubmit],
    );

    const handleSubmitClick = useCallback(() => {
        submitIntentRef.current = true;
    }, []);

    if (isSuccess) {
        return (
            <div className="flex flex-col items-center justify-center min-h-dvh text-center gap-8 px-4 animate-in fade-in duration-500">
                <div className="relative">
                    <div className="flex items-center justify-center size-24 rounded-full bg-green-100 dark:bg-green-900/30">
                        <CheckCircle className="size-12 text-green-600 dark:text-green-400" />
                    </div>
                    <div className="absolute -bottom-1 -end-1 flex items-center justify-center size-10 rounded-full bg-primary/10 border-4 border-background">
                        <PawPrint className="size-5 text-primary" />
                    </div>
                </div>
                <div className="flex flex-col gap-3 max-w-lg">
                    <h1 className="text-4xl font-bold tracking-tight">
                        {t("features.become-host.success.title")}
                    </h1>
                    <p className="text-lg text-muted-foreground leading-relaxed">
                        {t("features.become-host.success.description")}
                    </p>
                </div>
                <Button
                    size="lg"
                    className="rounded-4xl px-10 py-6 text-base"
                    onClick={() => router.push(routes.MyEstablishments())}
                >
                    {t("features.become-host.success.cta")}
                </Button>
            </div>
        );
    }

    const isPreForm = currentStepId === "welcome" || currentStepId === "host-type";
    const formSteps = activeSteps.filter((s): s is StepId => s !== "welcome" && s !== "host-type");
    const formStepIndex = (formSteps as StepId[]).indexOf(currentStepId);
    const showFooter = !isPreForm;

    return (
        <div className="flex flex-col min-h-dvh bg-background">
            <header className="flex items-center justify-between px-6 md:px-12 h-16 shrink-0 border-b">
                <Link href={routes.Home()}>
                    <Image
                        className="h-8 w-auto"
                        src="/logo_type.svg"
                        height={120}
                        width={30}
                        alt="Kennelo"
                    />
                </Link>
            </header>

            <form
                id="become-host-form"
                onSubmit={onFormSubmit}
                className="flex-1 flex items-start justify-center px-4 md:px-12 pb-28 pt-8 md:pt-16 overflow-y-auto"
            >
                <div className="w-full max-w-xl">
                    <div
                        key={currentStepId}
                        className={cn(
                            "animate-in fade-in duration-300",
                            directionRef.current === "forward"
                                ? "slide-in-from-right-8"
                                : "slide-in-from-left-8",
                        )}
                    >
                        {stepper.flow.switch({
                            welcome: () => <WelcomeStep onNext={handleNext} />,
                            "host-type": () => (
                                <HostTypeStep
                                    value={hostType}
                                    onChange={handleHostTypeChange}
                                    error={selectionError}
                                />
                            ),
                            "establishment-type": () => (
                                <EstablishmentTypeStep
                                    value={establishmentType}
                                    onChange={(type) => {
                                        setEstablishmentType(type);
                                        setSelectionError(undefined);
                                    }}
                                    error={selectionError}
                                />
                            ),
                            "establishment-info": () => (
                                <EstablishmentInfoStep control={control} isLoading={isLoading} />
                            ),
                            "contact-details": () => (
                                <ContactDetailsStep control={control} isLoading={isLoading} />
                            ),
                            address: () => <AddressStep control={control} isLoading={isLoading} />,
                            "business-info": () => (
                                <BusinessInfoStep control={control} isLoading={isLoading} />
                            ),
                            review: () => (
                                <ReviewStep
                                    watch={watch}
                                    onGoTo={handleGoTo}
                                    error={submitError}
                                    hostType={hostType}
                                />
                            ),
                        })}
                    </div>
                </div>
            </form>

            {currentStepId === "host-type" && (
                <footer className="fixed bottom-0 inset-x-0 bg-background z-10 border-t">
                    <div className="flex items-center justify-between px-6 md:px-12 py-4">
                        <Button
                            type="button"
                            variant="link"
                            className="text-base font-medium underline underline-offset-4 px-0"
                            onClick={handleBack}
                            disabled={isLoading}
                        >
                            {t("common.actions.back")}
                        </Button>
                        <Button
                            type="button"
                            className="rounded-4xl px-8 py-6 text-base"
                            onClick={handleNext}
                            disabled={!hostType}
                        >
                            {t("common.actions.next")}
                        </Button>
                    </div>
                </footer>
            )}

            {showFooter && (
                <footer className="fixed bottom-0 inset-x-0 bg-background z-10 border-t">
                    <div className="flex gap-1.5 px-6 md:px-12 pt-3">
                        {formSteps.map((stepId, index) => (
                            <div
                                key={stepId}
                                className="flex-1 h-1 rounded-full overflow-hidden bg-muted"
                            >
                                <div
                                    className={cn(
                                        "h-full rounded-full bg-primary",
                                        isJumpRef.current
                                            ? ""
                                            : "transition-all duration-500 ease-out",
                                        index <= formStepIndex ? "w-full" : "w-0",
                                    )}
                                />
                            </div>
                        ))}
                    </div>

                    <div className="flex items-center justify-between px-6 md:px-12 py-4">
                        <Button
                            type="button"
                            variant="link"
                            className="text-base font-medium underline underline-offset-4 px-0"
                            onClick={handleBack}
                            disabled={isLoading}
                        >
                            {t("common.actions.back")}
                        </Button>
                        {currentStepId === "review" ? (
                            <Button
                                type="submit"
                                form="become-host-form"
                                className="rounded-4xl px-8 py-6 text-base"
                                disabled={isLoading}
                                onClick={handleSubmitClick}
                            >
                                {isLoading
                                    ? t("features.become-host.steps.review.submitting")
                                    : t("features.become-host.steps.review.submitButton")}
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                className="rounded-4xl px-8 py-6 text-base"
                                onClick={handleNext}
                                disabled={isLoading}
                            >
                                {t("common.actions.next")}
                            </Button>
                        )}
                    </div>
                </footer>
            )}
        </div>
    );
}
