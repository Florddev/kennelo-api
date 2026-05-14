"use client";

import { Image } from "lucide-react";
import { useTranslations } from "next-intl";
import type { PetAttributeModel, PetModel } from "@workspace/modules/pets";
import { PetBadgesStrip } from "@/features/pets/components/pet-badges-strip";
import { PetTypeIllustration } from "./pet-type-illustration";
import { PetProfileReviews } from "./pet-profile-reviews";
import { PetAttributeCategory } from "../../../../../packages/modules/src/pets/types/attributes-categories.type";
import { cn } from "@workspace/ui/lib/utils";
import { SectionShapeSvg } from "@/components/svg/section-shape";
import { HeartPulse, type IconProps, InfoCircle, InfoSquare } from "@solar-icons/react";
import { DynamicIcon, type SolarIconName } from "@/components/dynamic-icon";

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
            <div
                className={cn(
                    "bg-muted size-8 min-w-8 rounded-[0.8rem] flex justify-center items-center ",
                    className,
                )}
            >
                <DynamicIcon
                    iconName={iconName as SolarIconName}
                    DefaultIcon={InfoSquare}
                    className="size-5 min-w-5 text-primary"
                    weight="Linear"
                />
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
}: {
    pet: PetModel;
    title?: string | null;
    categories?: PetAttributeCategory[] | null;
    children?: React.ReactNode;
    className?: string;
}) {
    const t = useTranslations();
    const yesText = t(YES_ACTION_KEY);
    const noText = t(NO_ACTION_KEY);
    const attrs = pet.groupAttributesByCategory(categories);

    if (!attrs || attrs.length === 0) return null;

    return (
        <div className="flex flex-col gap-3">
            {title && <h3 className="text-md font-semibold">{title}</h3>}
            <div className="flex flex-wrap gap-4">
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

export function PetDetailsSection({
    title,
    children,
    className,
    Icon,
}: {
    title: string;
    children: React.ReactNode;
    className?: string;
    Icon?: React.ComponentType<IconProps>;
}) {
    return (
        <div className={cn("flex flex-col gap-4", className)}>
            <div className="flex gap-2 items-center pb-0 z-10">
                {Icon && <Icon className="size-6" />}
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>

            <div className="flex flex-col gap-3 px-1.5">{children}</div>
        </div>
    );
}

export function PetProfileInfo({ pet, ageDisplay }: PetProfileInfoProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6 p-4 sm:px-0">
            <PetIdentityHeader pet={pet} />

            <div className="flex flex-col gap-2">
                <h2 className="text-lg font-semibold">{t("features.pets.profile.about")}</h2>
                {pet.about && (
                    <p className="text-sm leading-relaxed text-foreground/80">{pet.about}</p>
                )}
                <PetBadgesStrip pet={pet} ageDisplay={ageDisplay} />
            </div>

            <div className="flex flex-col gap-6">
                <PetDetailsSection
                    title={t("common.messages.summary")}
                    Icon={InfoCircle}
                    className="bg-amber-100 rounded-3xl p-4 relative overflow-hidden"
                >
                    <SectionShapeSvg className="text-amber-400 absolute top-0 left-0" />

                    <PetGroupedAttributesList
                        pet={pet}
                        title={t("common.messages.socialization")}
                        categories={["social"]}
                        className="bg-amber-50"
                    />
                    <PetGroupedAttributesList
                        pet={pet}
                        title={t("common.messages.boarding")}
                        categories={["behavior", "habitat", "hygiene", "care"]}
                        className="bg-amber-50"
                    />
                    <PetGroupedAttributesList
                        pet={pet}
                        title={t("common.messages.otherInformation")}
                        categories={["diet", "info"]}
                        className="bg-amber-50"
                    />
                </PetDetailsSection>

                <PetDetailsSection title={t("common.messages.health")} Icon={HeartPulse}>
                    <PetGroupedAttributesList pet={pet} categories={["health"]}>
                        {pet.isSterilized !== null && (
                            <PetAttributeItem
                                label={t("features.pets.fields.sterilized")}
                                value={pet.isSterilized ? t(YES_ACTION_KEY) : t(NO_ACTION_KEY)}
                                iconName="Scissors"
                            />
                        )}

                        <PetAttributeItem
                            label={t("features.pets.fields.microchip")}
                            value={
                                pet.hasMicrochip
                                    ? (pet.microchipNumber ?? t(YES_ACTION_KEY))
                                    : t(NO_ACTION_KEY)
                            }
                            iconName="Cpu"
                        />

                        {pet.healthNotes && (
                            <PetAttributeItem
                                label={t("features.pets.profile.medicalNotes")}
                                value={pet.healthNotes}
                                iconName="InfoSquare"
                            />
                        )}
                    </PetGroupedAttributesList>
                </PetDetailsSection>

                <div className="rounded-2xl border bg-muted/30 p-10 flex flex-col items-center gap-3 text-center">
                    <Image className="size-10 text-muted-foreground opacity-20" />
                    <div className="space-y-1">
                        <p className="font-medium text-sm">{t("common.messages.galleryEmpty")}</p>
                        <p className="text-xs text-muted-foreground">
                            {t("common.messages.galleryEmptyDescription")}
                        </p>
                    </div>
                </div>

                <PetProfileReviews />
            </div>
        </div>
    );
}
