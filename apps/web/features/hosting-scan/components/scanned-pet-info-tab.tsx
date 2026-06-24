"use client";

import { useTranslations } from "next-intl";

import type { PetModel } from "@workspace/modules/pets";

import {
    PetAttributeItem,
    PetBasicSection,
    PetDetailsSection,
    PetGroupedAttributesList,
} from "@/features/pets/components/pet-profile-info";

export function ScannedPetInfoTab({
    pet,
    ageDisplay,
}: {
    pet: PetModel;
    ageDisplay: string | null;
}) {
    const t = useTranslations();
    const petColorClass = pet.animalType?.getTailwindColorClass();
    const attributeClassName = `bg-${petColorClass}-50 text-${petColorClass}-900`;

    return (
        <div className="flex flex-col gap-6">
            <PetBasicSection
                pet={pet}
                ageDisplay={ageDisplay}
                weight="Linear"
                className={`bg-${petColorClass}-50 text-${petColorClass}-950`}
            />

            <PetDetailsSection>
                <PetGroupedAttributesList
                    pet={pet}
                    title={t("common.messages.health")}
                    categories={["health"]}
                    forceDisplay={
                        pet.isSterilized !== null || !!pet.microchipNumber || !!pet.healthNotes
                    }
                    className={attributeClassName}
                >
                    {pet.isSterilized !== null && (
                        <PetAttributeItem
                            label={t("features.pets.fields.sterilized")}
                            value={
                                pet.isSterilized ? t("common.actions.yes") : t("common.actions.no")
                            }
                            iconName="Scissors"
                            className={attributeClassName}
                        />
                    )}
                    {pet.microchipNumber && (
                        <PetAttributeItem
                            label={t("features.pets.fields.microchip")}
                            value={pet.microchipNumber}
                            iconName="Cpu"
                            className={attributeClassName}
                        />
                    )}
                    {pet.healthNotes && (
                        <PetAttributeItem
                            label={t("features.pets.profile.medicalNotes")}
                            value={pet.healthNotes}
                            iconName="InfoSquare"
                            className={attributeClassName}
                        />
                    )}
                </PetGroupedAttributesList>

                <PetGroupedAttributesList
                    pet={pet}
                    title={t("common.messages.boarding")}
                    categories={["behavior", "habitat", "hygiene", "care"]}
                    className={attributeClassName}
                />

                <PetGroupedAttributesList
                    pet={pet}
                    title={t("common.messages.otherInformation")}
                    categories={["diet", "info"]}
                    className={attributeClassName}
                />

                <PetGroupedAttributesList
                    pet={pet}
                    title={t("common.messages.socialization")}
                    categories={["social"]}
                    className={attributeClassName}
                />
            </PetDetailsSection>
        </div>
    );
}
