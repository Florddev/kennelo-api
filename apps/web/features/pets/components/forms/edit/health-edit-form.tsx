"use client";

import React, { useEffect, useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Cpu, DocumentMedicine, InfoSquare, Scissors } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import {
    getAnimalTypes,
    updatePet,
    upsertPetAttributes,
    updatePetHealthSchema,
    PetAttributeCategoryEnum,
    type UpdatePetHealthInput,
    type PetModel,
} from "@workspace/modules/pets";
import { InlineController } from "@/components/forms/inline-controller";
import { useAsyncState } from "@/hooks/use-async-state";
import { buildAttributePayload } from "@/features/pets/components/forms/create-pet-stepper.mappers";
import type { AttributeDraft } from "@/features/pets/components/forms/create-pet-stepper.types";
import { initDraftsFromPet, applyInlineValue } from "@/features/pets/utils/attribute-form-utils";
import { AttributeInline } from "@/features/pets/components/attribute-inline";
import { toast } from "sonner";

export function HealthEditForm({ pet }: { pet: PetModel }): React.ReactElement {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();

    const form = useForm<UpdatePetHealthInput>({
        resolver: zodResolver(updatePetHealthSchema),
        defaultValues: {
            isSterilized: pet.isSterilized ?? false,
            hasMicrochip: pet.hasMicrochip,
            microchipNumber: pet.microchipNumber ?? "",
            healthNotes: pet.healthNotes ?? "",
        },
    });

    const hasMicrochip = useWatch({ control: form.control, name: "hasMicrochip" });

    const [drafts, setDraftsState] = useState<Record<number, AttributeDraft>>(() =>
        initDraftsFromPet(pet),
    );

    useEffect(() => {
        form.reset({
            isSterilized: pet.isSterilized ?? false,
            hasMicrochip: pet.hasMicrochip,
            microchipNumber: pet.microchipNumber ?? "",
            healthNotes: pet.healthNotes ?? "",
        });
    }, [form, pet.isSterilized, pet.hasMicrochip, pet.microchipNumber, pet.healthNotes]);

    const { data: animalTypes } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
    });

    const healthDefinitions =
        animalTypes
            ?.find((type) => type.id === pet.animalTypeId)
            ?.attributeDefinitions?.filter((d) => d.category === PetAttributeCategoryEnum.HEALTH) ??
        [];

    const onSubmit = async (data: UpdatePetHealthInput) => {
        await execute(
            async () => {
                await updatePet(pet.id, {
                    isSterilized: data.isSterilized,
                    hasMicrochip: data.hasMicrochip,
                    microchipNumber: data.hasMicrochip ? data.microchipNumber || null : null,
                    healthNotes: data.healthNotes || null,
                });
                const payload = buildAttributePayload(healthDefinitions, drafts);
                if (payload) await upsertPetAttributes(pet.id, payload);
            },
            {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: ["pets", "detail", pet.id] });
                    toast.success(t("features.pets.edit.saveSuccess"));
                },
            },
        );
    };

    return (
        <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-3 pb-20 md:pb-0">
            <InlineController
                name="isSterilized"
                control={form.control}
                type="boolean"
                label={t("features.pets.fields.sterilized")}
                Icon={Scissors}
                isLoading={isLoading}
            />
            <InlineController
                name="hasMicrochip"
                control={form.control}
                type="boolean"
                label={t("features.pets.fields.microchip")}
                Icon={Cpu}
                isLoading={isLoading}
            />
            {hasMicrochip && (
                <InlineController
                    name="microchipNumber"
                    control={form.control}
                    type="text"
                    label={t("features.pets.fields.microchipNumber")}
                    Icon={DocumentMedicine}
                    placeholder={t("features.pets.create.placeholders.microchipNumber")}
                    isLoading={isLoading}
                />
            )}

            {healthDefinitions.length > 0 && (
                <div className="flex flex-col gap-3">
                    {healthDefinitions.map((definition) => (
                        <AttributeInline
                            key={definition.id}
                            definition={definition}
                            draft={drafts[definition.id]}
                            onChange={(v) =>
                                setDraftsState((prev) => applyInlineValue(prev, definition, v))
                            }
                            isLoading={isLoading}
                            petName={pet.name}
                        />
                    ))}
                </div>
            )}

            <InlineController
                name="healthNotes"
                control={form.control}
                type="textarea"
                label={t("features.pets.fields.healthNotes")}
                Icon={InfoSquare}
                placeholder={t("features.pets.create.placeholders.healthNotes")}
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
