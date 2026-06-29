"use client";

import { useTranslations } from "next-intl";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetTypeIllustration } from "./pet-type-illustration";
import { PetProfileReviews } from "./pet-profile-reviews";
import { PetAttributeCategory } from "../../../../../packages/modules/src/pets/types/attributes-categories.type";
import { cn } from "@workspace/ui/lib/utils";
import { GalleryMinimalistic, type IconProps, IconWeight, InfoSquare } from "@solar-icons/react";
import { DynamicIcon, type SolarIconName } from "@/components/dynamic-icon";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";
import { usePetReviews } from "../hooks/use-pet-reviews";
import Image from "next/image";
import React from "react";
import { Frame } from "@/components/frame";
import { SectionShapeSvg } from "@/components/svg/section-shape";
import { Separator } from "@workspace/ui/components/separator";
import { Sticky } from "@workspace/ui/components/sticky";

type PetProfileInfoProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

const YES_ACTION_KEY = "common.actions.yes";
const NO_ACTION_KEY = "common.actions.no";

function PetIdentityHeader({ pet, className }: { pet: PetModel; className: string }) {
    return (
        <div className={cn("flex gap-2 items-center", className)}>
            <div className="flex flex-col w-full">
                <h1 className="text-3xl font-bold tracking-tight">{pet.name}</h1>
                <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    {pet.animalType && <span>{pet.animalType.name}</span>}
                    {pet.getBreedLabel() && (
                        <>
                            {pet.animalType && <span>·</span>}
                            <span>{pet.getBreedLabel()}</span>
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
    weight,
}: {
    label: string;
    value: string;
    iconName?: string | null;
    className?: string;
    weight?: IconWeight;
}) {
    return (
        <div className="flex gap-2">
            <div className="bg-white rounded-[0.8rem] size-8 shrink-0 flex justify-center items-center overflow-hidden">
                <div
                    className={cn(
                        "flex justify-center p-1.5 items-center w-full h-full text-primary",
                        className,
                    )}
                >
                    <DynamicIcon
                        iconName={iconName as SolarIconName}
                        DefaultIcon={InfoSquare}
                        className="size-5 min-w-5"
                        weight={weight || "Linear"}
                    />
                </div>
            </div>
            <div className="flex flex-col">
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
    weight,
}: {
    pet: PetModel;
    title?: string | null | React.ReactNode;
    categories?: PetAttributeCategory[] | null;
    children?: React.ReactNode;
    className?: string;
    forceDisplay?: boolean;
    framed?: boolean;
    weight?: IconWeight;
}) {
    const t = useTranslations();
    const yesText = t(YES_ACTION_KEY);
    const noText = t(NO_ACTION_KEY);
    const attrs = pet.groupAttributesByCategory(categories);

    if (!attrs || (attrs.length === 0 && !forceDisplay)) return null;

    return (
        <div className={cn("flex flex-col gap-3", framed && "bg-muted/70 rounded-sm p-0.5 gap-0")}>
            {title && <div className={cn("text-sm font-semibold", framed && "p-2")}>{title}</div>}
            <div
                className={cn(
                    "flex flex-wrap gap-y-2 gap-x-6 px-2",
                    framed && "p-4 bg-white border rounded-sm",
                )}
            >
                {attrs.map(({ attributes }) =>
                    attributes.map((attr: PetAttributeModel) => (
                        <PetAttributeItem
                            key={attr.id}
                            label={attr.attributeDefinition?.label || ""}
                            value={attr.displayValue(yesText, noText)}
                            iconName={attr.attributeDefinition?.iconName}
                            className={className}
                            weight={weight}
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
    weight,
}: {
    pet: PetModel;
    ageDisplay: string | null;
    className?: string;
    weight?: IconWeight;
}) {
    const t = useTranslations();
    const commonClass = cn("bg-amber-50", className);

    return (
        <div className="grid grid-cols-2 gap-y-4 z-10 w-full">
            <PetAttributeItem
                label={t("features.pets.fields.age")}
                value={ageDisplay ?? "0"}
                iconName="Calendar"
                className={commonClass}
                weight={weight}
            />
            <PetAttributeItem
                label={t("features.pets.fields.sex")}
                value={t(`features.pets.sex.${pet.sex}`)}
                iconName={pet.sex === "male" ? "Men" : "Women"}
                className={commonClass}
                weight={weight}
            />
            <PetAttributeItem
                label={t("features.pets.fields.weight")}
                value={pet.weight !== null ? `${pet.weight} kg` : "0 kg"}
                iconName="Weigher"
                className={commonClass}
                weight={weight}
            />
            <PetAttributeItem
                label={t("features.pets.fields.microchip")}
                value={
                    pet.isSterilized ? (pet.microchipNumber ?? t(YES_ACTION_KEY)) : t(NO_ACTION_KEY)
                }
                iconName="Cpu"
                className={commonClass}
                weight={weight}
            />
        </div>
    );
}

export function PetSectionLabel({
    title,
    Icon,
    className,
    weight,
}: {
    title?: string;
    Icon?: React.ComponentType<IconProps>;
    className?: string;
    weight?: IconWeight;
}) {
    return (
        (Icon || title) && (
            <div className={cn("flex gap-1.5 items-center pb-0 z-10", className)}>
                {Icon && <Icon className="size-6" weight={weight} />}
                {title && <h2 className="text-xl font-semibold">{title}</h2>}
            </div>
        )
    );
}

export function PetDetailsSection({
    title,
    children,
    className,
    weight,
    Icon,
}: {
    title?: string;
    children: React.ReactNode;
    className?: string;
    weight?: IconWeight;
    Icon?: React.ComponentType<IconProps>;
}) {
    return (
        <div className={cn("flex flex-col gap-2 text-primary", className)}>
            <PetSectionLabel title={title} Icon={Icon} weight={weight} />
            <div className="flex flex-col gap-4">{children}</div>
        </div>
    );
}

export function PetProfileInfo({ pet, ageDisplay }: PetProfileInfoProps) {
    const t = useTranslations();
    const { reviews } = usePetReviews(pet.id);

    const hasImages = pet.images && pet.images.length > 0;
    const petColorClass = pet?.animalType?.getTailwindColorClass();

    return (
        <div className="flex flex-col gap-4 sm:px-0">
            <PetIdentityHeader pet={pet} className="p-4 pb-0" />

            <Separator className="opacity-30 mx-4" />

            <div className="relative flex flex-1 flex-col gap-1 px-4">
                <h3 className="text-xl font-semibold text-primary whitespace-nowrap">
                    {t("common.fields.description")}
                </h3>
                {pet.about ? (
                    <p className="text-xs text-primary">{pet.about}</p>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        {t("features.pets.noDescription")}
                    </p>
                )}
            </div>

            <div className="px-4">
                <Frame
                    header={
                        <PetSectionLabel
                            title={t("features.pets.profile.about")}
                            className="px-4 py-2"
                        />
                    }
                    className={`bg-${petColorClass}-100 p-0 gap-3`}
                    contentClassName="border-0 bg-transparent p-3 pt-0"
                >
                    <SectionShapeSvg
                        className={`absolute top-0 left-0 text-${petColorClass}-400`}
                    />
                    <PetBasicSection
                        pet={pet}
                        ageDisplay={ageDisplay}
                        weight="Linear"
                        className={`bg-${petColorClass}-50  text-${petColorClass}-950`}
                    />
                </Frame>
            </div>

            <Tabs defaultValue="overview" className="flex flex-col gap-0">
                <Sticky
                    top={0}
                    className="bg-card w-full z-20"
                    stickyClassName="border-b border-border/30"
                >
                    <TabsList variant="line" className="w-full">
                        <div className="grid grid-cols-3 w-full py-2 px-4">
                            <div className="flex justify-start">
                                <TabsTrigger value="overview" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.pets.edit.sections.general")}
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
                        </div>
                    </TabsList>
                </Sticky>

                <TabsContent value="overview" className="flex flex-col gap-4 p-4">
                    <div className="flex flex-col">
                        <PetSectionLabel title={t("features.pets.profile.overviewSectionTitle")} />
                        <p className="text-muted-foreground">
                            {t("features.pets.profile.overviewSectionDescription")}
                        </p>
                    </div>

                    <PetDetailsSection>
                        <div className="flex flex-col gap-4">
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.socialization")}
                                categories={["social"]}
                                className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                // weight="BoldDuotone"
                                // framed
                            />
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.boarding")}
                                categories={["behavior", "habitat", "hygiene", "care"]}
                                className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                // weight="BoldDuotone"
                                // framed
                            />
                            <PetGroupedAttributesList
                                pet={pet}
                                title={t("common.messages.otherInformation")}
                                categories={["diet", "info"]}
                                className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                // weight="BoldDuotone"
                                // framed
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
                                className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                // weight="BoldDuotone"
                                // framed
                            >
                                {pet.isSterilized !== null && (
                                    <PetAttributeItem
                                        label={t("features.pets.fields.sterilized")}
                                        value={
                                            pet.isSterilized ? t(YES_ACTION_KEY) : t(NO_ACTION_KEY)
                                        }
                                        iconName="Scissors"
                                        className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                        // weight="BoldDuotone"
                                    />
                                )}
                                {pet.microchipNumber && (
                                    <PetAttributeItem
                                        label={t("features.pets.fields.microchip")}
                                        value={pet.microchipNumber}
                                        iconName="Cpu"
                                        className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                        // weight="BoldDuotone"
                                    />
                                )}
                                {pet.healthNotes && (
                                    <PetAttributeItem
                                        label={t("features.pets.profile.medicalNotes")}
                                        value={pet.healthNotes}
                                        iconName="InfoSquare"
                                        className={`bg-${petColorClass}-50 text-${petColorClass}-900`}
                                        // weight="BoldDuotone"
                                    />
                                )}
                            </PetGroupedAttributesList>
                        </div>
                    </PetDetailsSection>
                </TabsContent>

                <TabsContent value="gallery" className="flex flex-col gap-4 p-4">
                    {hasImages ? (
                        <>
                            <div className="flex flex-col">
                                <PetSectionLabel
                                    title={t("features.pets.profile.galleryTitle", {
                                        name: pet.name,
                                    })}
                                />
                                <p className="text-muted-foreground">
                                    {t("features.pets.profile.galleryDescription", {
                                        name: pet.name,
                                    })}
                                </p>
                            </div>
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
                        </>
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

                <TabsContent value="reviews" className="flex flex-col gap-4 p-4">
                    {reviews.length > 0 && (
                        <div className="flex flex-col">
                            <PetSectionLabel title={t("features.pets.profile.reviewsTitle")} />
                            {reviews.length > 0 && (
                                <p className="text-muted-foreground">
                                    {t("features.pets.profile.reviewsCount", {
                                        count: reviews.length,
                                    })}
                                </p>
                            )}
                        </div>
                    )}

                    <PetProfileReviews pet={pet} />
                </TabsContent>
            </Tabs>
        </div>
    );
}
