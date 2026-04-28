"use client";

import {
    Activity,
    Cpu,
    Droplets,
    Heart,
    Home,
    Image,
    Info,
    LucideIcon,
    Scissors,
    SlidersHorizontal,
    Users,
    UtensilsCrossed,
} from "lucide-react";
import { useTranslations } from "next-intl";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetGallery } from "@/features/pets/components/pet-gallery";
import { PetBadgesStrip } from "@/features/pets/components/pet-badges-strip";
import { PetTypeIllustration } from "./pet-type-illustration";
import { KHeartBeat, KInfoCircle } from "@workspace/ui/icons";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";
import { PetProfileReviews } from "./pet-profile-reviews";
import { PetAttributeCategory } from "../../../../../packages/modules/src/pets/types/attributes-categories.type";
import { cn } from "@workspace/ui/lib/utils";
import { SectionShapeSvg } from "@/components/svg/section-shape";

type PetProfileInfoProps = {
    pet: PetModel;
    ageDisplay: string | null;
};

const categoriesIcons: Record<
    PetAttributeCategory,
    React.ComponentType<React.SVGProps<SVGSVGElement>>
> = {
    info: Info,
    behavior: SlidersHorizontal,
    social: Users,
    hygiene: Droplets,
    care: Heart,
    health: Activity,
    habitat: Home,
    diet: UtensilsCrossed,
};

const YES_ACTION_KEY = "common.actions.yes";
const NO_ACTION_KEY = "common.actions.no";

function PetIdentityHeader({ pet }: { pet: PetModel }) {
    return (
        <div className="flex gap-2 items-center">
            <div className="flex flex-col w-full">
                <div className="flex gap-2 items-center">
                    <h1 className="text-3xl font-bold tracking-tight">{pet.name}</h1>
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

export function PetAttributeItem({
    label,
    value,
    category,
    Icon,
}: {
    label: string;
    value: string;
    category?: PetAttributeCategory;
    Icon?: LucideIcon;
}) {
    const IconComponent =
        Icon || (category && categoriesIcons[category] ? categoriesIcons[category] : Info);

    return (
        <div className="flex gap-2">
            <div className="size-6 aspect-square rounded-[8px] flex justify-center items-center bg-card">
                <IconComponent className="size-3.5 text-muted-foreground/80" />
            </div>
            <div className="flex flex-col">
                <span className="text-xs text-primary font-medium">{label}</span>
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
}: {
    pet: PetModel;
    title?: string | null;
    categories?: PetAttributeCategory[] | null;
    children?: React.ReactNode;
}) {
    const t = useTranslations();
    const yesText = t(YES_ACTION_KEY);
    const noText = t(NO_ACTION_KEY);
    const attrs = pet.groupAttributesByCategory(categories);

    if (!attrs || attrs.length === 0) return null;

    return (
        <div className="flex flex-col gap-3">
            {title && <h3 className="text-md font-semibold">{title}</h3>}
            <div className="grid grid-cols-2 gap-2">
                {attrs.map(({ attributes }) =>
                    attributes.map((attr: PetAttributeModel) => (
                        <PetAttributeItem
                            key={attr.id}
                            label={attr.attributeDefinition?.label || ""}
                            value={attr.displayValue(yesText, noText)}
                            category={attr.attributeDefinition?.category}
                        />
                    )),
                )}
                {children}
            </div>
        </div>
    );
}

export function PetDetailsSection({
    title,
    children,
    className,
    Icon,
}: {
    title: string;
    children: React.ReactNode;
    className?: string;
    Icon?: React.ComponentType<{ className?: string; filled?: boolean }>;
}) {
    return (
        <div className={cn("flex flex-col gap-4", className)}>
            <div className="flex gap-1 items-center pb-0 z-10">
                {Icon && <Icon className="size-6 text-primary/70" filled />}
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>

            <div className="flex flex-col gap-3 px-1.5">{children}</div>
        </div>
    );
}

export function PetProfileInfo({ pet, ageDisplay }: PetProfileInfoProps) {
    const t = useTranslations();

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

                        <Tabs defaultValue="overview" className="flex flex-col gap-2">
                            <TabsList variant="line" className="flex justify-between w-full">
                                <TabsTrigger value="overview" className="w-fit hover:text-red-500">
                                    <span data-slot="tab-label">
                                        {t("common.messages.overview")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                                <TabsTrigger value="gallery" className="w-fit">
                                    <span data-slot="tab-label">
                                        {t("common.messages.gallery")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                                <TabsTrigger value="reviews" className="w-fit">
                                    <span data-slot="tab-label">
                                        {t("common.messages.reviews")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent value="overview" className="flex flex-col gap-6">
                                <PetDetailsSection
                                    title={t("common.messages.summary")}
                                    Icon={KInfoCircle}
                                    className="bg-amber-100 rounded-3xl py-2.5 px-3 relative overflow-hidden"
                                >
                                    <SectionShapeSvg className="text-amber-400 absolute top-0 left-0" />

                                    <PetGroupedAttributesList
                                        pet={pet}
                                        title={t("common.messages.socialization")}
                                        categories={["social"]}
                                    />
                                    <PetGroupedAttributesList
                                        pet={pet}
                                        title={t("common.messages.boarding")}
                                        categories={["behavior", "habitat", "hygiene", "care"]}
                                    />
                                    <PetGroupedAttributesList
                                        pet={pet}
                                        title={t("common.messages.otherInformation")}
                                        categories={["diet", "info"]}
                                    />
                                </PetDetailsSection>

                                <PetDetailsSection
                                    title={t("common.messages.health")}
                                    Icon={KHeartBeat}
                                >
                                    <PetGroupedAttributesList pet={pet} categories={["health"]}>
                                        {pet.isSterilized !== null && (
                                            <PetAttributeItem
                                                label={t("features.pets.fields.sterilized")}
                                                value={
                                                    pet.isSterilized
                                                        ? t(YES_ACTION_KEY)
                                                        : t(NO_ACTION_KEY)
                                                }
                                                Icon={Scissors}
                                            />
                                        )}

                                        <PetAttributeItem
                                            label={t("features.pets.fields.microchip")}
                                            value={
                                                pet.hasMicrochip
                                                    ? (pet.microchipNumber ?? t(YES_ACTION_KEY))
                                                    : t(NO_ACTION_KEY)
                                            }
                                            Icon={Cpu}
                                        />
                                    </PetGroupedAttributesList>

                                    {pet.healthNotes && (
                                        <div className="space-y-2.5">
                                            <p className="text-md font-medium">
                                                {t("features.pets.profile.medicalNotes")}
                                            </p>
                                            <div className="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/10 p-4">
                                                <p className="text-sm text-amber-800/80 dark:text-amber-300/80 leading-relaxed">
                                                    {pet.healthNotes}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </PetDetailsSection>
                            </TabsContent>
                            <TabsContent value="gallery">
                                <div className="rounded-2xl border bg-muted/30 p-10 flex flex-col items-center gap-3 text-center">
                                    <Image className="size-10 text-muted-foreground opacity-20" />
                                    <div className="space-y-1">
                                        <p className="font-medium text-sm">
                                            {t("common.messages.galleryEmpty")}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {t("common.messages.galleryEmptyDescription")}
                                        </p>
                                    </div>
                                </div>
                            </TabsContent>
                            <TabsContent value="reviews">
                                <PetProfileReviews />
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>
            </div>
        </div>
    );
}
