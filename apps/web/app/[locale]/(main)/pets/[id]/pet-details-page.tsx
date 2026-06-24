"use client";

import { ArrowLeft, PenNewSquare } from "@solar-icons/react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PetProfileInfo } from "@/features/pets/components/pet-profile-info";
import { PetImageEmptyState } from "@/features/pets/components/pet-image-empty-state";
import { PetDetailSkeleton } from "@/features/pets/components/pet-detail-skeleton";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { formatAgeDisplay } from "@/features/pets/lib/pet-age";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import Link from "next/link";
import { useIsMobile } from "@/hooks/use-mobile";

type Query = { id: string };

export default function PetDetailsPage() {
    const t = useTranslations();
    const { params, routes } = useNavigation<Query>();
    const { pet, isLoading } = usePet(params.id);
    const isMobile = useIsMobile();
    const { user } = useAuth();

    if (!pet && isLoading) {
        return <PetDetailSkeleton />;
    }

    if (!pet) {
        return null;
    }

    const ageDisplay = formatAgeDisplay(
        pet.birthDate,
        (count) => t("features.pets.age.years", { count }),
        (count) => t("features.pets.age.months", { count }),
    );

    const isOwner = user?.id === pet.userId;
    const typeCode = pet.animalType?.code?.toLowerCase() ?? "";
    const images = pet.getGalleryImages();

    return (
        <DetailPageLayout
            images={images}
            altPrefix={pet.name}
            emptyState={
                <PetImageEmptyState typeCode={typeCode} typeName={pet.animalType?.name ?? ""} />
            }
            desktopCtaLabel={t("features.pets.profile.viewPhotos", { count: images.length })}
            headerStart={
                <Button size="icon-sm" className="text-primary bg-card hover:bg-muted" asChild>
                    <Link href={routes.MyPets()}>
                        <ArrowLeft />
                    </Link>
                </Button>
            }
            headerEnd={
                <>
                    {isOwner && (
                        <Button
                            size="sm"
                            className="text-primary bg-card hover:bg-muted gap-1.5"
                            asChild
                        >
                            <Link
                                href={
                                    isMobile
                                        ? routes.PetEditPage({ id: pet.id })
                                        : routes.PetEditGeneral({ id: pet.id })
                                }
                            >
                                <PenNewSquare />
                                {t("common.actions.edit")}
                            </Link>
                        </Button>
                    )}
                </>
            }
            footer={
                isOwner ? (
                    <div className="h-16 bg-card border-t px-2 flex justify-center items-center sm:hidden">
                        <Button className="w-full" size="xl">
                            {t("features.pets.profile.findHost", { name: pet.name })}
                        </Button>
                    </div>
                ) : undefined
            }
            className="pb-6"
        >
            <PetProfileInfo pet={pet} ageDisplay={ageDisplay} />
        </DetailPageLayout>
    );
}
