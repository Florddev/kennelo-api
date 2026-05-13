"use client";

import React, { useState } from "react";
import { useTranslations } from "next-intl";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Button } from "@workspace/ui/components/button";
import {
    getAnimalTypes,
    upsertPetAttributes,
    PetAttributeCategoryEnum,
    type PetModel,
} from "@workspace/modules/pets";
import { useAsyncState } from "@/hooks/use-async-state";
import { buildAttributePayload } from "@/features/pets/components/forms/create-pet-stepper.mappers";
import type { AttributeDraft } from "@/features/pets/components/forms/create-pet-stepper.types";
import { initDraftsFromPet, applyInlineValue } from "@/features/pets/utils/attribute-form-utils";
import { AttributeInline } from "@/features/pets/components/attribute-inline";
import { toast } from "sonner";

export function PersonalityEditForm({ pet }: { pet: PetModel }): React.ReactElement {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();

    const [drafts, setDraftsState] = useState<Record<number, AttributeDraft>>(() =>
        initDraftsFromPet(pet),
    );

    const { data: animalTypes } = useQuery({
        queryKey: ["animal-types"],
        queryFn: getAnimalTypes,
    });

    const allDefinitions =
        animalTypes?.find((type) => type.id === pet.animalTypeId)?.attributeDefinitions ?? [];

    const definitions = allDefinitions.filter(
        (d) => d.category !== PetAttributeCategoryEnum.HEALTH,
    );

    const handleSubmit = async () => {
        const payload = buildAttributePayload(definitions, drafts);
        if (!payload) return;
        await execute(() => upsertPetAttributes(pet.id, payload), {
            onSuccess: () => {
                queryClient.invalidateQueries({ queryKey: ["pets", "detail", pet.id] });
                toast.success(t("features.pets.edit.saveSuccess"));
            },
        });
    };

    if (definitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground py-4">
                {t("features.pets.create.attributes.defaultPetName")}
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-3 pb-8 md:pb-0">
            {definitions.map((definition) => (
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
            <div className="fixed bottom-0 inset-x-0 p-2 bg-background border-t z-10 md:static md:p-0 md:border-0 md:bg-transparent md:mt-2">
                <Button onClick={handleSubmit} disabled={isLoading} size="xl" className="w-full">
                    {t("common.actions.save")}
                </Button>
            </div>
        </div>
    );
}
