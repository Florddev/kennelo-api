"use client";

import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import { Library, TextSquare } from "@solar-icons/react";
import { type Control, type FieldValues, type Path, useController } from "react-hook-form";

import { getAnimalBreeds } from "@workspace/modules/pets";

import { InlineList } from "@/components/forms/inline-inputs/inline-list";
import { InlineText } from "@/components/forms/inline-inputs/inline-text";
import { type InlineOption } from "@/components/forms/inline-inputs/types";

const OTHER_VALUE = "__other__";

type BreedSelectFieldProps<TFieldValues extends FieldValues> = {
    control: Control<TFieldValues>;
    animalTypeId: string | null | undefined;
    isLoading?: boolean;
    className?: string;
};

export function BreedSelectField<TFieldValues extends FieldValues>({
    control,
    animalTypeId,
    isLoading,
    className,
}: BreedSelectFieldProps<TFieldValues>) {
    const t = useTranslations();

    const { data: breeds = [] } = useQuery({
        queryKey: ["animal-breeds", animalTypeId],
        queryFn: () => getAnimalBreeds(animalTypeId ?? undefined),
        enabled: Boolean(animalTypeId),
    });

    const { field: breedIdField } = useController({
        control,
        name: "animalBreedId" as Path<TFieldValues>,
    });
    const { field: breedField } = useController({
        control,
        name: "breed" as Path<TFieldValues>,
    });

    const [isOther, setIsOther] = useState<boolean>(
        () => !breedIdField.value && Boolean(breedField.value),
    );

    const options = useMemo<InlineOption[]>(() => {
        const breedOptions: InlineOption[] = breeds.map((breed) => ({
            value: breed.id,
            label: breed.label,
        }));
        breedOptions.push({ value: OTHER_VALUE, label: t("features.pets.fields.breedOther") });
        return breedOptions;
    }, [breeds, t]);

    let listValue = "";
    if (breedIdField.value) {
        listValue = String(breedIdField.value);
    } else if (isOther) {
        listValue = OTHER_VALUE;
    }

    const handleSelect = (value: string) => {
        if (value === OTHER_VALUE) {
            setIsOther(true);
            breedIdField.onChange(null);
            return;
        }

        setIsOther(false);
        breedIdField.onChange(value || null);
        breedField.onChange(null);
    };

    return (
        <div className="flex flex-col gap-3">
            <InlineList
                label={t("features.pets.fields.breed")}
                Icon={Library}
                placeholder={t("features.pets.fields.breedPlaceholder")}
                isLoading={isLoading}
                options={options}
                value={listValue}
                onChange={(value) => handleSelect(String(value ?? ""))}
                className={className}
            />
            {isOther && (
                <InlineText
                    label={t("features.pets.fields.breedCustom")}
                    Icon={TextSquare}
                    placeholder={t("features.pets.fields.breedCustomPlaceholder")}
                    isLoading={isLoading}
                    value={breedField.value ? String(breedField.value) : ""}
                    onChange={(value) => breedField.onChange(value)}
                    className={className}
                />
            )}
        </div>
    );
}
