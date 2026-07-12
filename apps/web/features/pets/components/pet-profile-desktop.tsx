"use client";

import type { ReactNode } from "react";

import { useTranslations } from "next-intl";
import type { PetModel } from "@workspace/modules/pets";
import { Separator } from "@workspace/ui/components/separator";

import { SplitPageLayout } from "@/components/layouts/split-page-layout";
import { MediaGallery } from "@/components/media/media-gallery";

import {
    PetAboutFrame,
    PetCharacterHabits,
    PetDescription,
    PetIdentityHeader,
    PetSectionLabel,
} from "./pet-profile-info";
import { PetProfileReviews } from "./pet-profile-reviews";
import { PetDesktopGallery } from "./pet-desktop-gallery";
import { usePetReviews } from "../hooks/use-pet-reviews";

type PetProfileDesktopProps = {
    pet: PetModel;
    ageDisplay: string | null;
    images: string[];
    emptyState?: ReactNode;
    headerStart?: ReactNode;
    headerEnd?: ReactNode;
    footer?: ReactNode;
};

export function PetProfileDesktop({
    pet,
    ageDisplay,
    images,
    emptyState,
    headerStart,
    headerEnd,
    footer,
}: PetProfileDesktopProps) {
    const t = useTranslations();
    const { reviews } = usePetReviews(pet.id);

    return (
        <SplitPageLayout isRoot={false}>
            <SplitPageLayout.Sidebar>
                <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <div>{headerStart}</div>
                        <div className="flex gap-0.5">{headerEnd}</div>
                    </div>

                    <PetIdentityHeader pet={pet} />

                    <Separator className="opacity-30" />

                    <PetDescription pet={pet} />

                    <PetAboutFrame pet={pet} ageDisplay={ageDisplay} />

                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col">
                            <PetSectionLabel
                                title={t("features.pets.profile.overviewSectionTitle")}
                            />
                            <p className="text-muted-foreground">
                                {t("features.pets.profile.overviewSectionDescription")}
                            </p>
                        </div>
                        <PetCharacterHabits pet={pet} />
                    </div>

                    <Separator className="opacity-30" />

                    <div className="flex flex-col gap-4">
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
                        <PetProfileReviews pet={pet} />
                    </div>

                    {footer && <div className="pt-2">{footer}</div>}
                </div>
            </SplitPageLayout.Sidebar>

            <SplitPageLayout.Content cleanContainer>
                <div className="flex flex-col gap-6">
                    <MediaGallery
                        images={images}
                        altPrefix={pet.name}
                        emptyState={emptyState}
                        desktopCtaLabel={t("features.pets.profile.viewPhotos", {
                            count: images.length,
                        })}
                    />
                    <PetDesktopGallery name={pet.name} images={images} />
                </div>
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}
