"use client";

import React, { useEffect, useMemo } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMessages, useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import {
    Calendar,
    CalendarAdd,
    CalendarMark,
    DocumentMedicine,
    GallerySend,
    InfoCircle,
    InfoSquare,
    Library,
    Men,
    Paw,
    SoundwaveSquare,
    TextSquare,
    Weigher,
    Women,
} from "@solar-icons/react";
import Image from "next/image";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import {
    getAnimalTypes,
    updatePet,
    uploadPetAvatar,
    updatePetGeneralSchema,
    type UpdatePetGeneralInput,
    type PetModel,
} from "@workspace/modules/pets";
import { InlineController, type InlineOption } from "@/components/forms/inline-controller";
import { ImagePickerDialog } from "@/components/forms/image-picker-dialog";
import { useAsyncState } from "@/hooks/use-async-state";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import { readNestedMessage } from "@/features/pets/utils/attribute-form-utils";
import { toast } from "sonner";
import { RowLabel } from "@/components/forms/inline-inputs/shared";

export function GeneralEditForm({ pet }: { pet: PetModel }): React.ReactElement {
    const t = useTranslations();
    const messages = useMessages();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const { execute: executeAvatar, isLoading: isAvatarLoading } = useAsyncState();

    const { data: animalTypes = [] } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
    });

    const animalTypeOptions = useMemo<InlineOption[]>(
        () =>
            animalTypes.map((type) => {
                const key = `features.pets.types.${type.code}`;
                const typeName = readNestedMessage(messages, key) ? t(key) : type.name;
                const normalized = type.code.toLowerCase();
                return {
                    value: String(type.id),
                    label: typeName,
                    visual: isIllustratedType(normalized) ? (
                        <Image
                            src={`/illustrations/pets/${normalized}.svg`}
                            alt={typeName}
                            width={36}
                            height={36}
                            className="size-9 object-contain"
                        />
                    ) : (
                        <Paw className="size-9 text-primary" />
                    ),
                };
            }),
        [animalTypes, messages, t],
    );

    const form = useForm<UpdatePetGeneralInput>({
        resolver: zodResolver(updatePetGeneralSchema),
        defaultValues: {
            name: pet.name,
            animalTypeId: pet.animalTypeId,
            breed: pet.breed ?? "",
            sex: pet.sex ?? "",
            birthDate: pet.birthDate ?? null,
            weight: pet.weight ?? null,
            adoptionDate: pet.adoptionDate ?? null,
            about: pet.about ?? "",
        },
    });

    useEffect(() => {
        form.reset({
            name: pet.name,
            animalTypeId: pet.animalTypeId,
            breed: pet.breed ?? "",
            sex: pet.sex ?? "",
            birthDate: pet.birthDate ?? null,
            weight: pet.weight ?? null,
            adoptionDate: pet.adoptionDate ?? null,
            about: pet.about ?? "",
        });
    }, [
        form,
        pet.name,
        pet.animalTypeId,
        pet.breed,
        pet.sex,
        pet.birthDate,
        pet.weight,
        pet.adoptionDate,
        pet.about,
    ]);

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: ["pets", "detail", pet.id] });

    const handleAvatarChange = async (files: File[]) => {
        const file = files[0];
        if (!file) return;
        await executeAvatar(() => uploadPetAvatar(pet.id, file), {
            onSuccess: () => {
                invalidate();
                toast.success(t("features.pets.edit.saveSuccess"));
            },
        });
    };

    const onSubmit = async (data: UpdatePetGeneralInput) => {
        await execute(
            () =>
                updatePet(pet.id, {
                    name: data.name,
                    animalTypeId: data.animalTypeId || undefined,
                    breed: data.breed || null,
                    sex: (data.sex as "male" | "female" | "unknown") || null,
                    birthDate: data.birthDate,
                    weight: data.weight,
                    adoptionDate: data.adoptionDate,
                    about: data.about || null,
                }),
            {
                onSuccess: () => {
                    invalidate();
                    toast.success(t("features.pets.edit.saveSuccess"));
                },
            },
        );
    };

    const avatarUrl = pet.getAvatarUrl();

    return (
        <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-3">
            <ImagePickerDialog value={[]} onChange={handleAvatarChange} maxFiles={1} mode="direct">
                <div
                    className={cn(
                        "flex items-center gap-4 p-4 border rounded-sm cursor-pointer hover:bg-muted/50 transition-colors",
                        isAvatarLoading && "opacity-50 pointer-events-none",
                    )}
                >
                    <div className="relative aspect-14/9 h-16 rounded-sm overflow-hidden bg-muted shrink-0">
                        {avatarUrl ? (
                            <Image src={avatarUrl} alt={pet.name} fill className="object-cover" />
                        ) : (
                            <div className="size-full flex items-center justify-center">
                                <GallerySend className="size-6 text-muted-foreground" />
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col gap-0.5">
                        <span className="text-sm font-semibold">
                            {t("features.pets.create.steps.media.avatarLabel")}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {t("features.pets.create.steps.avatar.pick")}
                        </span>
                    </div>
                </div>
            </ImagePickerDialog>

            <RowLabel
                Icon={SoundwaveSquare}
                className="mt-4"
                label={t("features.pets.edit.generalGroups.identity")}
            />

            <InlineController
                name="name"
                control={form.control}
                type="text"
                label={t("features.pets.fields.name")}
                Icon={TextSquare}
                isLoading={isLoading}
            />

            <div className="grid md:grid-cols-2 gap-3">
                <InlineController
                    name="animalTypeId"
                    control={form.control}
                    type="card-list"
                    label={t("features.pets.fields.animalType")}
                    Icon={Paw}
                    options={animalTypeOptions}
                    isLoading={isLoading}
                />
                <InlineController
                    name="breed"
                    control={form.control}
                    type="text"
                    label={t("features.pets.fields.breed")}
                    Icon={Library}
                    isLoading={isLoading}
                />
            </div>

            <InlineController
                name="sex"
                Icon={DocumentMedicine}
                control={form.control}
                type="button-list"
                label={t("features.pets.fields.sex")}
                isLoading={isLoading}
                options={[
                    { label: t("features.pets.sex.male"), value: "male", Icon: Men },
                    { label: t("features.pets.sex.female"), value: "female", Icon: Women },
                ]}
            />

            <RowLabel
                Icon={CalendarAdd}
                className="mt-4"
                label={t("features.pets.edit.generalGroups.keyDates")}
            />

            <InlineController
                name="birthDate"
                control={form.control}
                type="date"
                label={t("features.pets.fields.birthDate")}
                Icon={Calendar}
                isLoading={isLoading}
                allowApproximate
            />
            <InlineController
                Icon={CalendarMark}
                name="adoptionDate"
                control={form.control}
                type="date"
                label={t("features.pets.fields.adoptionDate")}
                isLoading={isLoading}
            />

            <RowLabel
                Icon={InfoCircle}
                className="mt-4"
                label={t("features.pets.edit.generalGroups.characteristics")}
            />

            <InlineController
                name="weight"
                control={form.control}
                type="number"
                label={t("features.pets.fields.weight")}
                Icon={Weigher}
                step={0.1}
                min={0}
                max={200}
                isLoading={isLoading}
            />
            <InlineController
                name="about"
                control={form.control}
                type="textarea"
                label={t("features.pets.fields.about")}
                Icon={InfoSquare}
                placeholder={t("features.pets.create.placeholders.about")}
                isLoading={isLoading}
            />

            <div className="fixed bottom-0 inset-x-0 p-2 bg-background border-t z-10 md:static md:p-0 md:border-0 md:bg-transparent md:mt-2">
                <Button type="submit" size="xl" disabled={isLoading} className="w-full">
                    {t("common.actions.save")}
                </Button>
            </div>
        </form>
    );
}
