"use client";

import { useTranslations } from "next-intl";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetTypeIllustration } from "./pet-type-illustration";
import { PetProfileReviews } from "./pet-profile-reviews";
import { PetAttributeCategory } from "../../../../../packages/modules/src/pets/types/attributes-categories.type";
import { cn } from "@workspace/ui/lib/utils";
import {
    AltArrowRight,
    ChecklistMinimalistic,
    GalleryMinimalistic,
    type IconProps,
    InfoSquare,
    UserHeart,
} from "@solar-icons/react";
import { DynamicIcon, type SolarIconName } from "@/components/dynamic-icon";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";
import { useAuth, UserAvatar } from "@/features/auth";
import { usePetReviews } from "../hooks/use-pet-reviews";
import Image from "next/image";
import React from "react";
import { Frame } from "@/components/frame";

type PetProfileInfoProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

const YES_ACTION_KEY = "common.actions.yes";
const NO_ACTION_KEY = "common.actions.no";

function PetIdentityHeader({ pet }: { pet: PetModel }) {
    return (
        <div className="flex gap-2 items-center">
            <div className="flex flex-col w-full">
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
            <PetTypeIllustration
                code={pet.animalType?.code || ""}
                name={pet.name}
                className="size-12"
            />
        </div>
    );
}

export function PetAttributeItem({
    label,
    value,
    iconName,
    className,
}: {
    label: string;
    value: string;
    iconName?: string | null;
    className?: string;
}) {
    return (
        <div className="flex gap-2">
            <div className="bg-white size-8 min-w-8 rounded-[0.8rem] flex justify-center items-center overflow-hidden">
                <div className={cn("flex justify-center items-center w-full h-full", className)}>
                    <DynamicIcon
                        iconName={iconName as SolarIconName}
                        DefaultIcon={InfoSquare}
                        className="size-5 min-w-5 text-primary"
                        weight="Linear"
                    />
                </div>
            </div>
            <div className="flex flex-col gap-0.5">
                <span className="text-sm text-primary font-medium">{label}</span>
                <span className="text-xs text-muted-foreground">{value}</span>
            </div>
        </div>
    );
}

export function PetGroupedAttributesList({
    pet,
    title,
    categories,
    children,
    className,
    forceDisplay = false,
    framed = false,
}: {
    pet: PetModel;
    title?: string | null | React.ReactNode;
    categories?: PetAttributeCategory[] | null;
    children?: React.ReactNode;
    className?: string;
    forceDisplay?: boolean;
    framed?: boolean;
}) {
    const t = useTranslations();
    const yesText = t(YES_ACTION_KEY);
    const noText = t(NO_ACTION_KEY);
    const attrs = pet.groupAttributesByCategory(categories);

    if (!attrs || (attrs.length === 0 && !forceDisplay)) return null;

    return (
        <div className={cn("flex flex-col gap-3", framed && "bg-muted rounded-sm p-0.5 gap-0")}>
            {title && <div className={cn("text-sm font-semibold", framed && "p-2")}>{title}</div>}
            <div className={cn("flex flex-wrap gap-4", framed && "p-4 bg-white border rounded-sm")}>
                {attrs.map(({ attributes }) =>
                    attributes.map((attr: PetAttributeModel) => (
                        <PetAttributeItem
                            key={attr.id}
                            label={attr.attributeDefinition?.label || ""}
                            value={attr.displayValue(yesText, noText)}
                            iconName={attr.attributeDefinition?.iconName}
                            className={className}
                        />
                    )),
                )}
                {children}
            </div>
        </div>
    );
}

export function PetBasicSection({
    pet,
    ageDisplay,
    className,
}: {
    pet: PetModel;
    ageDisplay: string | null;
    className?: string;
}) {
    const t = useTranslations();
    const commonClass = cn("bg-amber-50", className);

    return (
        <div className="grid grid-cols-2 gap-y-4 z-10">
            <PetAttributeItem
                label={t("features.pets.fields.age")}
                value={ageDisplay ?? "0"}
                iconName="Calendar"
                className={commonClass}
            />
            <PetAttributeItem
                label={t("features.pets.fields.sex")}
                value={t(`features.pets.sex.${pet.sex}`)}
                iconName={pet.sex === "male" ? "Men" : "Women"}
                className={commonClass}
            />
            <PetAttributeItem
                label={t("features.pets.fields.weight")}
                value={pet.weight !== null ? `${pet.weight} kg` : "0 kg"}
                iconName="Weigher"
                className={commonClass}
            />
            <PetAttributeItem
                label={t("features.pets.fields.microchip")}
                value={
                    pet.hasMicrochip ? (pet.microchipNumber ?? t(YES_ACTION_KEY)) : t(NO_ACTION_KEY)
                }
                iconName="Cpu"
                className={commonClass}
            />
        </div>
    );
}

export function PetSectionLabel({
    title,
    Icon,
    className,
}: {
    title?: string;
    Icon?: React.ComponentType<IconProps>;
    className?: string;
}) {
    return (
        (Icon || title) && (
            <div className={cn("flex gap-1.5 items-center pb-0 z-10", className)}>
                {Icon && <Icon className="size-6" />}
                {title && <h2 className="text-xl font-semibold">{title}</h2>}
            </div>
        )
    );
}

export function PetDetailsSection({
    title,
    children,
    className,
    Icon,
}: {
    title?: string;
    children: React.ReactNode;
    className?: string;
    Icon?: React.ComponentType<IconProps>;
}) {
    return (
        <div className={cn("flex flex-col gap-2 text-primary", className)}>
            <PetSectionLabel title={title} Icon={Icon} />
            <div className="flex flex-col gap-4">{children}</div>
        </div>
    );
}

export function PetProfileInfo({ pet, ageDisplay }: PetProfileInfoProps) {
    const t = useTranslations();
    const { user } = useAuth();
    const { reviews } = usePetReviews(pet.id);

    const hasImages = pet.images && pet.images.length > 0;

    return (
        <div className="flex flex-col gap-4 p-4 sm:px-0">
            <PetIdentityHeader pet={pet} />

            <div
                data-slot="pet-about-banner"
                className="relative flex flex-col items-center gap-4 overflow-hidden rounded-sm bg-secondary/20 p-4"
            >
                <span
                    aria-hidden
                    className="pointer-events-none absolute -top-5 -start-5 h-20 w-24 rounded-[50%] bg-secondary"
                />
                {pet.about && (
                    <div className="relative flex items-end gap-4 w-full">
                        <div className="relative flex flex-1 flex-col gap-2">
                            <h3 className="text-xl font-semibold text-primary whitespace-nowrap">
                                {t("features.pets.profile.about")}
                            </h3>
                            <p className="text-xs text-primary/80">{pet.about}</p>
                        </div>
                        <Image
                            src="/keny_illustration.png"
                            alt=""
                            aria-hidden
                            width={122}
                            height={92}
                            className="relative h-full shrink-0 object-contain"
                        />
                    </div>
                )}
                <PetBasicSection pet={pet} ageDisplay={ageDisplay} className="bg-secondary/5" />
            </div>

            {/* <PetDetailsSection
                title={t("features.pets.edit.sections.general")}
                Icon={Notes}
                className="bg-amber-100 rounded-sm p-4 relative overflow-hidden gap-4"
            >
                <SectionShapeSvg className="text-amber-400 absolute top-0 left-0 z-0" />
                <PetBasicSection pet={pet} ageDisplay={ageDisplay} />
            </PetDetailsSection> */}

            <Tabs defaultValue="overview" className="flex flex-col gap-2">
                <TabsList variant="line" className="grid grid-cols-3 w-full">
                    <div className="flex justify-start">
                        <TabsTrigger value="overview" className="max-w-fit">
                            <span data-slot="tab-label" className="!text-base">
                                {t("common.messages.overview")}
                            </span>
                            <span data-slot="tab-indicator" />
                        </TabsTrigger>
                    </div>
                    <div className="flex justify-center">
                        <TabsTrigger value="reviews" className="max-w-fit">
                            <span data-slot="tab-label" className="!text-base">
                                {t("common.messages.reviews")}
                            </span>
                            <span data-slot="tab-indicator" />
                        </TabsTrigger>
                    </div>
                    <div className="flex justify-end">
                        <TabsTrigger value="gallery" className="max-w-fit">
                            <span data-slot="tab-label" className="!text-base">
                                {t("common.messages.gallery")}
                            </span>
                            <span data-slot="tab-indicator" />
                        </TabsTrigger>
                    </div>
                </TabsList>

                <TabsContent value="overview" className="flex flex-col gap-6">
                    <PetDetailsSection
                        title={t("common.messages.summary")}
                        Icon={ChecklistMinimalistic}
                    >
                        <div className="flex flex-col gap-2">
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.socialization")}
                                categories={["social"]}
                                className="bg-muted"
                                framed
                            />
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.boarding")}
                                categories={["behavior", "habitat", "hygiene", "care"]}
                                className="bg-muted"
                                framed
                            />
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.otherInformation")}
                                categories={["diet", "info"]}
                                className="bg-muted"
                                framed
                            />
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.health")}
                                categories={["health"]}
                                forceDisplay={
                                    !!(
                                        pet.isSterilized !== null ||
                                        pet.microchipNumber ||
                                        pet.healthNotes
                                    )
                                }
                                className="bg-muted"
                                framed
                            >
                                {pet.isSterilized !== null && (
                                    <PetAttributeItem
                                        label={t("features.pets.fields.sterilized")}
                                        value={
                                            pet.isSterilized ? t(YES_ACTION_KEY) : t(NO_ACTION_KEY)
                                        }
                                        iconName="Scissors"
                                        className="bg-muted"
                                    />
                                )}
                                {pet.microchipNumber && (
                                    <PetAttributeItem
                                        label={t("features.pets.fields.microchip")}
                                        value={pet.microchipNumber}
                                        iconName="Cpu"
                                        className="bg-muted"
                                    />
                                )}
                                {pet.healthNotes && (
                                    <PetAttributeItem
                                        label={t("features.pets.profile.medicalNotes")}
                                        value={pet.healthNotes}
                                        iconName="InfoSquare"
                                        className="bg-muted"
                                    />
                                )}
                            </PetGroupedAttributesList>
                        </div>
                    </PetDetailsSection>

                    <PetDetailsSection title={t("features.pets.profile.owner")} Icon={UserHeart}>
                        <Frame>
                            <div className="flex justify-between items-center gap-4 rounded-2xl">
                                <UserAvatar user={user} className="size-12" />
                                <div className="flex flex-col w-full">
                                    <h2 className="text-lg font-semibold">{user?.getFullName()}</h2>
                                    <p className="text-sm text-muted-foreground">{user?.email}</p>
                                </div>
                                <AltArrowRight className="size-8 text-muted-foreground" />
                            </div>
                        </Frame>
                    </PetDetailsSection>
                </TabsContent>

                <TabsContent value="gallery">
                    {hasImages ? (
                        <div className="grid grid-cols-2 gap-2">
                            {pet.images.map((image) => (
                                <div
                                    key={image.id}
                                    className="aspect-square rounded-2xl overflow-hidden"
                                >
                                    <Image
                                        src={image.url}
                                        alt={pet.name}
                                        width={300}
                                        height={300}
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed p-10 flex flex-col items-center gap-3 text-center">
                            <GalleryMinimalistic
                                weight="BoldDuotone"
                                className="size-10 text-muted-foreground opacity-20"
                            />
                            <div className="space-y-1">
                                <p className="font-medium text-sm">
                                    {t("common.messages.galleryEmpty")}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {t("common.messages.galleryEmptyDescription")}
                                </p>
                            </div>
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="reviews">
                    <PetDetailsSection
                        title={
                            reviews.length > 0 ? t("features.pets.profile.reviewsTitle") : undefined
                        }
                        className="gap-0"
                    >
                        {reviews.length > 0 && (
                            <p className="text-muted-foreground mb-2">
                                {t("features.pets.profile.reviewsCount", {
                                    count: reviews.length,
                                })}
                            </p>
                        )}
                        <PetProfileReviews pet={pet} />
                    </PetDetailsSection>
                </TabsContent>
            </Tabs>
        </div>
    );
}
