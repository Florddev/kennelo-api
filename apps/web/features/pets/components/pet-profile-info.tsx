"use client";

import { HeartPulse, Leaf, Star, Zap } from "lucide-react";
import { useTranslations } from "next-intl";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetGallery } from "@/features/pets/components/pet-gallery";
import { PetBadgesStrip } from "@/features/pets/components/pet-badges-strip";
import { PetSectionHeader } from "@/features/pets/components/pet-section-header";
import {
    PetAttributeBehaviorCard,
    PetAttributeCareCard,
} from "@/features/pets/components/pet-attribute-card";
import { PetHealthSection } from "@/features/pets/components/pet-health-section";
import { PetTypeIllustration } from "./pet-type-illustration";
import { Badge } from "@workspace/ui/components/badge";
import { KHeart } from "@workspace/ui/icons";

type PetProfileInfoProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

function isBooleanAttr(attr: PetAttributeModel): boolean {
    return typeof attr.value === "boolean";
}

function isNonBooleanAttr(attr: PetAttributeModel): boolean {
    return typeof attr.value !== "boolean";
}

function PetIdentityHeader({ pet }: { pet: PetModel }) {
    return (
        <div className="flex gap-2 items-center">
            {/* <PetTypeIllustration
                code={pet.animalType?.code || ""}
                name={pet.name}
                className="size-12"
            />
            <div className="flex justify-between items-start w-full">
                <div className="flex flex-col">
                    <h1 className="text-3xl font-bold tracking-tight">{pet.name}</h1>
                    <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        {pet.animalType && <span>{pet.animalType.name}</span>}
                        {pet.breed && (
                            <>
                                {pet.animalType && <span>·</span>}
                                <span>{pet.breed}</span>
                            </>
                        )}
                    </div>
                </div>
                <Badge variant="outline" className="text-amber-500 bg-amber-100 border-0">
                    <Star className="size-4" />
                    4.4
                </Badge>
            </div> */}

            <div className="flex flex-col w-full">
                <div className="flex gap-2 items-center">
                    <h1 className="text-3xl font-bold tracking-tight">{pet.name}</h1>
                    <Badge variant="outline" className="text-amber-500 bg-amber-100 border-0">
                        <Star className="size-4" />
                        4.4
                    </Badge>
                </div>
                <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    {pet.animalType && <span>{pet.animalType.name}</span>}
                    {pet.breed && (
                        <>
                            {pet.animalType && <span>·</span>}
                            <span>{pet.breed}</span>
                        </>
                    )}
                </div>
            </div>
            <PetTypeIllustration
                code={pet.animalType?.code || ""}
                name={pet.name}
                className="size-12"
            />
        </div>
    );
}

function PetBehaviorsSection({ attrs, label }: { attrs: PetModel["attributes"]; label: string }) {
    if (!attrs || attrs.length === 0) return null;
    return (
        <div className="space-y-4">
            <PetSectionHeader
                icon={<HeartPulse className="size-4 text-amber-600 dark:text-amber-400" />}
                title={label}
                count={attrs.length}
            />
            <div className="grid grid-cols-2 gap-3">
                {attrs.map((attr) => (
                    <PetAttributeBehaviorCard key={attr.id} attr={attr} />
                ))}
            </div>
        </div>
    );
}

function PetCareSection({ attrs, label }: { attrs: PetModel["attributes"]; label: string }) {
    if (!attrs || attrs.length === 0) return null;
    return (
        <div className="space-y-4">
            <PetSectionHeader
                icon={<Leaf className="size-4 text-amber-600 dark:text-amber-400" />}
                title={label}
                count={attrs.length}
            />
            <div className="grid grid-cols-2 gap-3">
                {attrs.map((attr) => (
                    <PetAttributeCareCard key={attr.id} attr={attr} />
                ))}
            </div>
        </div>
    );
}

export function PetProfileInfo({ pet, ageDisplay }: PetProfileInfoProps) {
    const t = useTranslations();

    const booleanAttrs = pet.attributes?.filter(isBooleanAttr) ?? [];
    const careAttrs = pet.attributes?.filter(isNonBooleanAttr) ?? [];

    return (
        <div className="space-y-6">
            <div className="flex flex-col">
                <PetGallery pet={pet} />
                <div className="-mt-6 z-10 bg-card rounded-3xl p-4 sm:mt-0 sm:px-0">
                    <div className="flex flex-col gap-6">
                        <PetIdentityHeader pet={pet} />

                        <div className="flex flex-col gap-2">
                            <h2 className="text-lg font-semibold">
                                {t("features.pets.profile.about")}
                            </h2>
                            {pet.about && (
                                <p className="text-sm leading-relaxed text-foreground/80">
                                    {pet.about}
                                </p>
                            )}
                            <PetBadgesStrip pet={pet} ageDisplay={ageDisplay} />
                        </div>

                        <div className="flex flex-col gap-3">
                            <div className="flex justify-between">
                                <div className="flex flex-col items-center gap-1 w-1/3">
                                    <span className="text-sm font-bold text-primary">Overview</span>
                                    <span className="h-[3px] bg-primary w-4 rounded-full" />
                                </div>
                                <div className="flex flex-col items-center w-1/3">
                                    <span className="text-sm font-medium text-muted-foreground">
                                        Gallery
                                    </span>
                                </div>
                                <div className="flex flex-col items-center w-1/3">
                                    <span className="text-sm font-medium text-muted-foreground">
                                        Reviews
                                    </span>
                                </div>
                            </div>

                            <div className="rounded-2xl bg-amber-100 p-3 flex flex-col gap-4">
                                <div className="flex gap-1 items-center">
                                    <KHeart className="size-8 text-amber-600" filled />
                                    <h2 className="text-lg font-semibold">
                                        {t("features.pets.profile.behaviors")}
                                    </h2>
                                </div>
                                <div className="grid grid-cols-2">
                                    <div className="flex gap-2">
                                        <div className="size-6 rounded flex justify-center items-center bg-amber-50">
                                            <Zap className="size-4 text-muted-foreground" />
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-sm text-primary font-medium">
                                                {"Niveau d'énergie"}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                Faible
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <PetBehaviorsSection
                                attrs={booleanAttrs}
                                label={t("features.pets.profile.behaviors")}
                            />

                            <PetCareSection
                                attrs={careAttrs}
                                label={t("features.pets.profile.care")}
                            />
                            <PetHealthSection pet={pet} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
