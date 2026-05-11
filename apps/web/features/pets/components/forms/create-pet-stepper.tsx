"use client";

import { useCallback, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import {
    type AnimalTypeModel,
    createPet,
    createPetSchema,
    getAnimalTypes,
    type CreatePetInput,
    updatePet,
    uploadPetAvatar,
    upsertPetAttributes,
} from "@workspace/modules/pets";
import { type FieldPath } from "react-hook-form";
import { FormStepper } from "@/components/forms/stepper/form-stepper";
import { type FormStepDefinition } from "@/components/forms/stepper/stepper-types";
import { useAsyncState } from "@/hooks/use-async-state";
import { useNavigation } from "@/hooks/use-navigation";
import {
    useOptimizedPetImageUpload,
    type UploadProgress,
} from "@/hooks/use-optimized-pet-image-upload";
import { CATEGORY_ORDER, Step, StepGroup } from "./create-pet-stepper.constants";
import {
    applyAttributeDraftPatch,
    buildAttributePayload,
    buildCreatePetPayload,
} from "./create-pet-stepper.mappers";
import { buildCategoryDefinitions, findSelectedAnimalType } from "./create-pet-stepper.selectors";
import { type AttributeDraft } from "./create-pet-stepper.types";
import { AnimalTypeStep } from "./step/animal-type-step";
import { AttributesCategoryStep } from "./step/attributes-category-step";
import { IdentityStep } from "./step/identity-step";
import { IntroStep } from "./step/intro-step";
import { MediaStep } from "./step/media-step";
import { ProfileStep } from "./step/profile-step";

export function CreatePetStepper() {
    const t = useTranslations();
    const { routes, router } = useNavigation();
    const { execute, isLoading } = useAsyncState();
    const { uploadCompressedImages } = useOptimizedPetImageUpload();
    const [formKey, setFormKey] = useState(0);
    const [createdPetId, setCreatedPetId] = useState<string | null>(null);
    const [selectedAnimalTypeValue, setSelectedAnimalTypeValue] = useState<string | null>(null);
    const [typeError, setTypeError] = useState<string | undefined>();
    const [attributeDrafts, setAttributeDrafts] = useState<Record<number, AttributeDraft>>({});
    const [avatarFile, setAvatarFile] = useState<File | null>(null);
    const [imageFiles, setImageFiles] = useState<File[]>([]);

    const { data: animalTypes = [] } = useQuery<AnimalTypeModel[]>({
        queryKey: ["pets", "animal-types"],
        queryFn: getAnimalTypes,
    });

    const selectedAnimalType = useMemo(
        () => findSelectedAnimalType(animalTypes, selectedAnimalTypeValue),
        [animalTypes, selectedAnimalTypeValue],
    );

    const selectedAnimalAttributes = useMemo(
        () => selectedAnimalType?.attributeDefinitions ?? [],
        [selectedAnimalType],
    );

    const categoryDefinitions = useMemo(
        () => buildCategoryDefinitions(CATEGORY_ORDER, selectedAnimalAttributes),
        [selectedAnimalAttributes],
    );

    const setDraft = useCallback((definitionId: number, patch: Partial<AttributeDraft>) => {
        setAttributeDrafts((previous) => applyAttributeDraftPatch(previous, definitionId, patch));
    }, []);

    const steps: FormStepDefinition<CreatePetInput>[] = [
        {
            id: Step.GENERAL_INTRO,
            fields: [],
            groupId: StepGroup.GENERAL,
            component: () => (
                <IntroStep
                    label={t("features.pets.create.steps.generalIntro.label")}
                    title={t("features.pets.create.steps.generalIntro.title")}
                    description={t("features.pets.create.steps.generalIntro.description")}
                />
            ),
        },
        {
            id: Step.ANIMAL_TYPE,
            fields: [],
            groupId: StepGroup.GENERAL,
            canProceed: async (form) => {
                if (!selectedAnimalType) {
                    setTypeError(t("features.pets.create.steps.type.error"));
                    return false;
                }

                form.setValue("animalTypeId", selectedAnimalType.id);
                setTypeError(undefined);
                return true;
            },
            component: () => (
                <AnimalTypeStep
                    animalTypes={animalTypes}
                    value={selectedAnimalTypeValue}
                    error={typeError}
                    onChange={(value) => {
                        setSelectedAnimalTypeValue(value);
                        setCreatedPetId(null);
                        setAttributeDrafts({});
                        setTypeError(undefined);
                    }}
                />
            ),
        },
        {
            id: Step.IDENTITY_BASICS,
            fields: ["name", "sex", "breed", "birthDate", "adoptionDate", "weight", "about"],
            groupId: StepGroup.GENERAL,
            component: ({ control, isLoading: loading }) => (
                <IdentityStep
                    control={control}
                    isLoading={loading}
                    avatarFile={avatarFile}
                    onAvatarChange={setAvatarFile}
                />
            ),
        },
        {
            id: Step.PROFILE,
            fields: ["isSterilized", "hasMicrochip", "microchipNumber"],
            groupId: StepGroup.GENERAL,
            canProceed: async (form) => {
                const hasMicrochip = form.getValues("hasMicrochip");
                const microchipNumber = (form.getValues("microchipNumber") ?? "").trim();

                if (hasMicrochip && microchipNumber.length === 0) {
                    form.setError("microchipNumber", {
                        message: t("features.pets.create.validation.microchipRequired"),
                    });
                    await form.trigger(["microchipNumber"], { shouldFocus: true });
                    return false;
                }

                if (!hasMicrochip) {
                    form.setValue("microchipNumber", "");
                }

                const payload = buildCreatePetPayload(form.getValues());
                const setFieldError = (
                    field: FieldPath<CreatePetInput>,
                    error: { message: string },
                ) => form.setError(field, error);

                if (createdPetId) {
                    const updatedPet = await execute(() => updatePet(createdPetId, payload), {
                        displayError: true,
                        setFieldError,
                    });

                    return Boolean(updatedPet);
                }

                const createdPet = await execute(() => createPet(payload), {
                    displayError: true,
                    setFieldError,
                });

                if (!createdPet) {
                    return false;
                }

                setCreatedPetId(createdPet.id);
                return true;
            },
            component: ({ control, isLoading: loading }) => (
                <ProfileStep control={control} isLoading={loading} />
            ),
        },
        {
            id: Step.ATTRIBUTES_INTRO,
            fields: [],
            groupId: StepGroup.ATTRIBUTES,
            component: () => (
                <IntroStep
                    label={t("features.pets.create.steps.attributesIntro.label")}
                    title={t("features.pets.create.steps.attributesIntro.title")}
                    description={t("features.pets.create.steps.attributesIntro.description")}
                />
            ),
        },
        ...categoryDefinitions.map<FormStepDefinition<CreatePetInput>>(
            ({ category, definitions }) => ({
                id: `${StepGroup.ATTRIBUTES}-${category}`,
                fields: [],
                groupId: StepGroup.ATTRIBUTES,
                isVisible: () => definitions.length > 0,
                component: ({ control }) => (
                    <AttributesCategoryStep
                        category={category}
                        definitions={definitions}
                        drafts={attributeDrafts}
                        setDraft={setDraft}
                        control={control}
                    />
                ),
            }),
        ),
        {
            id: Step.COMPLETION_INTRO,
            fields: [],
            groupId: StepGroup.COMPLETION,
            component: () => (
                <IntroStep
                    label={t("features.pets.create.steps.completionIntro.label")}
                    title={t("features.pets.create.steps.completionIntro.title")}
                    description={t("features.pets.create.steps.completionIntro.description")}
                />
            ),
        },
        {
            id: Step.MEDIA,
            fields: [],
            groupId: StepGroup.COMPLETION,
            component: () => (
                <MediaStep imageFiles={imageFiles} onImageFilesChange={setImageFiles} />
            ),
        },
    ];

    const onSubmit = async (
        values: CreatePetInput,
        setFieldError?: (field: FieldPath<CreatePetInput>, error: { message: string }) => void,
    ) => {
        let petId = createdPetId;

        if (!petId) {
            const createdPet = await execute(() => createPet(buildCreatePetPayload(values)), {
                displayError: true,
                setFieldError,
            });

            if (!createdPet) {
                return;
            }

            petId = createdPet.id;
            setCreatedPetId(createdPet.id);
        }

        const attributePayload = buildAttributePayload(selectedAnimalAttributes, attributeDrafts);

        if (attributePayload) {
            const attributesResult = await execute(
                () => upsertPetAttributes(petId, attributePayload),
                {
                    displayError: true,
                },
            );

            if (!attributesResult) {
                return;
            }
        }

        if (avatarFile) {
            const avatarResult = await execute(() => uploadPetAvatar(petId, avatarFile), {
                displayError: true,
            });

            if (!avatarResult) {
                return;
            }
        }

        if (imageFiles.length > 0) {
            const progressToastId = toast.loading(t("features.pets.create.upload.initializing"));

            try {
                await uploadCompressedImages(petId, imageFiles, (progress: UploadProgress) => {
                    const progressText =
                        progress.stage === "compressing"
                            ? t("features.pets.create.upload.compressing", {
                                  current: progress.current,
                                  total: progress.total,
                              })
                            : t("features.pets.create.upload.uploading", {
                                  current: progress.current,
                                  total: progress.total,
                              });

                    toast.loading(progressText, { id: progressToastId });
                });

                toast.success(t("features.pets.create.upload.success"), { id: progressToastId });
            } catch (error) {
                const errorMessage =
                    error instanceof Error ? error.message : t("features.pets.create.upload.error");

                toast.error(errorMessage, { id: progressToastId });
                return;
            }
        }

        setFormKey((previous) => previous + 1);
        setCreatedPetId(null);
        setSelectedAnimalTypeValue(null);
        setAttributeDrafts({});
        setAvatarFile(null);
        setImageFiles([]);
        router.push(routes.PetDetails({ id: petId }));
    };

    return (
        <FormStepper<CreatePetInput>
            key={formKey}
            schema={createPetSchema}
            defaultValues={{
                animalTypeId: 0,
                name: "",
                breed: "",
                birthDate: "",
                sex: null,
                weight: null,
                isSterilized: null,
                hasMicrochip: false,
                microchipNumber: "",
                adoptionDate: "",
                about: "",
                healthNotes: "",
            }}
            steps={steps}
            groups={Object.values(StepGroup)}
            labels={{
                back: t("common.actions.back"),
                next: t("common.actions.next"),
                submit: isLoading
                    ? t("features.pets.create.steps.final.submitting")
                    : t("features.pets.create.steps.final.submit"),
            }}
            onSubmit={onSubmit}
            isLoading={isLoading}
        />
    );
}
