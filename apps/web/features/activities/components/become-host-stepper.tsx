"use client";

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import {
    createActivity,
    createActivitySchema,
    type CreateActivityInput,
    type ActivityTypeValue,
} from "@workspace/modules/activities";
import { type AnimalTypeModel, getAnimalTypes } from "@workspace/modules/pets";
import type { FormStepDefinition } from "@/components/forms/stepper/stepper-types";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth";
import { AddressStep } from "./step/address-step";
import { BusinessInfoStep } from "./step/business-info-step";
import { ContactDetailsStep } from "./step/contact-details-step";
import { ActivityInfoStep } from "./step/activity-info-step";
import { ActivityTypeStep } from "./step/activity-type-step";
import { AnimalTypesStep } from "./step/animal-types-step";
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
    ACTIVITY_TYPE = "activity-type",
    ANIMAL_TYPES = "animal-types",
    ACTIVITY_INFO = "activity-info",
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
    const [activityType, setActivityType] = useState<ActivityTypeValue | null>(null);
    const [selectionError, setSelectionError] = useState<string | undefined>();
    const [selectedAnimalTypeIds, setSelectedAnimalTypeIds] = useState<string[]>([]);
    const [animalTypesError, setAnimalTypesError] = useState<string | undefined>();

    const { data: animalTypes = [] } = useQuery<AnimalTypeModel[]>({
        queryKey: ["pets", "animal-types"],
        queryFn: getAnimalTypes,
    });

    const steps: FormStepDefinition<CreateActivityInput>[] = [
        {
            id: Step.WELCOME,
            fields: [],
            groupId: StepGroup.HOST_SELECTION,
            component: () => <WelcomeStep />,
        },
        {
            id: Step.ACTIVITY_TYPE,
            fields: [],
            groupId: StepGroup.HOST_SELECTION,
            canProceed: async (form) => {
                if (!activityType) {
                    setSelectionError(t("features.become-host.steps.activityType.error"));
                    return false;
                }

                setSelectionError(undefined);
                form.setValue("type", activityType);

                if (activityType === "pet-sitter" && user) {
                    form.setValue("name", user.getFullName());
                    form.setValue("phone", user.phone ?? "");
                    form.setValue("email", user.email ?? "");
                }

                return true;
            },
            component: () => (
                <ActivityTypeStep
                    value={activityType}
                    onChange={(value) => {
                        setActivityType(value);
                        setSelectionError(undefined);
                    }}
                    error={selectionError}
                />
            ),
        },
        {
            id: Step.ANIMAL_TYPES,
            fields: ["animalTypeIds"],
            groupId: StepGroup.HOST_SELECTION,
            canProceed: async (form) => {
                if (selectedAnimalTypeIds.length === 0) {
                    setAnimalTypesError(t("features.become-host.steps.animalTypes.error"));
                    return false;
                }

                setAnimalTypesError(undefined);
                form.setValue("animalTypeIds", selectedAnimalTypeIds);
                return true;
            },
            component: () => (
                <AnimalTypesStep
                    animalTypes={animalTypes}
                    value={selectedAnimalTypeIds}
                    error={animalTypesError}
                    onChange={(value) => {
                        setSelectedAnimalTypeIds(value);
                        setAnimalTypesError(undefined);
                    }}
                />
            ),
        },
        {
            id: Step.ACTIVITY_INFO,
            fields: ["name", "description"],
            groupId: StepGroup.HOST_DETAILS,
            component: ({ control, isLoading: loading }) => (
                <ActivityInfoStep control={control} isLoading={loading} />
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
                <ReviewStep
                    control={control}
                    isLoading={loading}
                    error={undefined}
                    steps={steps}
                    animalTypes={animalTypes}
                />
            ),
        },
    ];

    const onSubmit = async (values: CreateActivityInput) => {
        const result = await execute(() => createActivity(values), {
            displayError: true,
        });

        if (result) {
            await refreshUser();
            setActivityType(null);
            setSelectedAnimalTypeIds([]);
            setFormKey((prev) => prev + 1);

            router.push(routes.MyActivities());
        }
    };

    return (
        <FormStepper<CreateActivityInput>
            key={formKey}
            schema={createActivitySchema}
            defaultValues={{
                animalTypeIds: [],
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
            className="w-full max-w-none"
        />
    );
}
