"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import {
    createEstablishment,
    createEstablishmentSchema,
    type CreateEstablishmentInput,
    type EstablishmentTypeValue,
} from "@workspace/modules/establishments";
import type { FormStepDefinition } from "@/components/forms/stepper/stepper-types";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth";
import { AddressStep } from "./step/address-step";
import { BusinessInfoStep } from "./step/business-info-step";
import { ContactDetailsStep } from "./step/contact-details-step";
import { EstablishmentInfoStep } from "./step/establishment-info-step";
import { EstablishmentTypeStep } from "./step/establishment-type-step";
import { ReviewStep } from "./step/review-step";
import { WelcomeStep } from "./step/welcome-step";
import { FormStepper } from "@/components/forms/stepper/form-stepper";
import { useNavigation } from "@/hooks/use-navigation";

enum StepGroup {
    HOST_SELECTION = "host-selection",
    HOST_DETAILS = "host-details",
    REVIEW = "review",
}

enum Step {
    WELCOME = "welcome",
    ESTABLISHMENT_TYPE = "establishment-type",
    ESTABLISHMENT_INFO = "establishment-info",
    CONTACT_DETAILS = "contact-details",
    ADDRESS = "address",
    BUSINESS_INFO = "business-info",
    REVIEW = "review",
}

export function BecomeHostStepper() {
    const t = useTranslations();
    const { routes, router } = useNavigation();
    const { execute, isLoading } = useAsyncState();
    const { refreshUser, user } = useAuth();
    const [formKey, setFormKey] = useState(0);
    const [establishmentType, setEstablishmentType] = useState<EstablishmentTypeValue | null>(null);
    const [selectionError, setSelectionError] = useState<string | undefined>();

    const steps: FormStepDefinition<CreateEstablishmentInput>[] = [
        {
            id: Step.WELCOME,
            fields: [],
            groupId: StepGroup.HOST_SELECTION,
            component: () => <WelcomeStep />,
        },
        {
            id: Step.ESTABLISHMENT_TYPE,
            fields: [],
            groupId: StepGroup.HOST_SELECTION,
            canProceed: async (form) => {
                if (!establishmentType) {
                    setSelectionError(t("features.become-host.steps.establishmentType.error"));
                    return false;
                }

                setSelectionError(undefined);
                form.setValue("type", establishmentType);

                if (establishmentType === "pet-sitter" && user) {
                    form.setValue("name", user.getFullName());
                    form.setValue("phone", user.phone ?? "");
                    form.setValue("email", user.email ?? "");
                }

                return true;
            },
            component: () => (
                <EstablishmentTypeStep
                    value={establishmentType}
                    onChange={(value) => {
                        setEstablishmentType(value);
                        setSelectionError(undefined);
                    }}
                    error={selectionError}
                />
            ),
        },
        {
            id: Step.ESTABLISHMENT_INFO,
            fields: ["name", "description"],
            groupId: StepGroup.HOST_DETAILS,
            component: ({ control, isLoading: loading }) => (
                <EstablishmentInfoStep control={control} isLoading={loading} />
            ),
        },
        {
            id: Step.CONTACT_DETAILS,
            fields: ["phone", "email", "website"],
            groupId: StepGroup.HOST_DETAILS,
            component: ({ control, isLoading: loading }) => (
                <ContactDetailsStep control={control} isLoading={loading} />
            ),
        },
        {
            id: Step.ADDRESS,
            fields: [
                "address.line1",
                "address.line2",
                "address.city",
                "address.postalCode",
                "address.region",
                "address.country",
            ],
            groupId: StepGroup.HOST_DETAILS,
            component: ({ control, isLoading: loading }) => (
                <AddressStep control={control} isLoading={loading} />
            ),
        },
        {
            id: Step.BUSINESS_INFO,
            fields: ["siret"],
            groupId: StepGroup.HOST_DETAILS,
            component: ({ control, isLoading: loading }) => (
                <BusinessInfoStep control={control} isLoading={loading} />
            ),
        },
        {
            id: Step.REVIEW,
            fields: [],
            groupId: StepGroup.REVIEW,
            component: ({ control, isLoading: loading }) => (
                <ReviewStep control={control} isLoading={loading} error={undefined} steps={steps} />
            ),
        },
    ];

    const onSubmit = async (values: CreateEstablishmentInput) => {
        const result = await execute(() => createEstablishment(values), {
            displayError: true,
        });

        if (result) {
            await refreshUser();
            setFormKey((prev) => prev + 1);

            router.push(routes.MyEstablishments());
        }
    };

    return (
        <FormStepper<CreateEstablishmentInput>
            key={formKey}
            schema={createEstablishmentSchema}
            defaultValues={{
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
            }}
            steps={steps}
            groups={Object.values(StepGroup)}
            labels={{
                back: t("common.actions.back"),
                next: t("common.actions.next"),
                submit: isLoading
                    ? t("features.become-host.steps.review.submitting")
                    : t("features.become-host.steps.review.submitButton"),
            }}
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="w-full max-w-2xl mx-auto"
        />
    );
}
