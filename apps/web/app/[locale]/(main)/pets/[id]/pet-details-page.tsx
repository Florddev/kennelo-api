"use client";

import { ArrowLeft, PenNewSquare, ChatRoundLine } from "@solar-icons/react";
import { useTranslations } from "next-intl";
import type { PetModel } from "@workspace/modules/pets";
import { Button } from "@workspace/ui/components/button";
import { usePet } from "@/features/pets/hooks/use-pet";
import { PetProfileInfo } from "@/features/pets/components/pet-profile-info";
import { PetProfileDesktop } from "@/features/pets/components/pet-profile-desktop";
import { PetImageEmptyState } from "@/features/pets/components/pet-image-empty-state";
import { PetDetailSkeleton } from "@/features/pets/components/pet-detail-skeleton";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useOpenConversation } from "@/features/conversations/hooks/use-open-conversation";
import { formatAgeDisplay } from "@/features/pets/lib/pet-age";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import Link from "next/link";
import { useIsMobile } from "@/hooks/use-mobile";

type Query = { id: string };

function PetHeaderActions({
    isOwner,
    canContactOwner,
    isMobile,
    mobileEditHref,
    desktopEditHref,
    editLabel,
    contactLabel,
    isContactPending,
    onContact,
}: {
    isOwner: boolean;
    canContactOwner: boolean;
    isMobile: boolean;
    mobileEditHref: string;
    desktopEditHref: string;
    editLabel: string;
    contactLabel: string;
    isContactPending: boolean;
    onContact: () => void;
}) {
    return (
        <>
            {isOwner && (
                <Button size="sm" className="text-primary bg-card hover:bg-muted gap-1.5" asChild>
                    <Link href={isMobile ? mobileEditHref : desktopEditHref}>
                        <PenNewSquare />
                        {editLabel}
                    </Link>
                </Button>
            )}
            {canContactOwner && (
                <Button
                    size="icon-sm"
                    className="text-primary bg-card hover:bg-muted"
                    disabled={isContactPending}
                    onClick={onContact}
                    aria-label={contactLabel}
                >
                    <ChatRoundLine />
                </Button>
            )}
        </>
    );
}

function PetOwnerFooter({ label }: { label: string }) {
    return (
        <div className="h-16 bg-card border-t px-2 flex justify-center items-center sm:hidden">
            <Button className="w-full" size="xl">
                {label}
            </Button>
        </div>
    );
}

function PetDesktopHeaderActions({
    isOwner,
    canContactOwner,
    editHref,
    editLabel,
    contactLabel,
    isContactPending,
    onContact,
}: {
    isOwner: boolean;
    canContactOwner: boolean;
    editHref: string;
    editLabel: string;
    contactLabel: string;
    isContactPending: boolean;
    onContact: () => void;
}) {
    return (
        <>
            {isOwner && (
                <Button variant="flat" asChild>
                    <Link href={editHref}>
                        <PenNewSquare />
                        {editLabel}
                    </Link>
                </Button>
            )}
            {canContactOwner && (
                <Button
                    variant="flat"
                    size="icon-sm"
                    disabled={isContactPending}
                    onClick={onContact}
                    aria-label={contactLabel}
                >
                    <ChatRoundLine />
                </Button>
            )}
        </>
    );
}

function PetDetailDesktopView({
    pet,
    ageDisplay,
    images,
    typeCode,
    isOwner,
    canContactOwner,
    backHref,
    editHref,
    isContactPending,
    onContact,
}: {
    pet: PetModel;
    ageDisplay: string | null;
    images: string[];
    typeCode: string;
    isOwner: boolean;
    canContactOwner: boolean;
    backHref: string;
    editHref: string;
    isContactPending: boolean;
    onContact: () => void;
}) {
    const t = useTranslations();

    return (
        <PetProfileDesktop
            pet={pet}
            ageDisplay={ageDisplay}
            images={images}
            emptyState={
                <PetImageEmptyState typeCode={typeCode} typeName={pet.animalType?.name ?? ""} />
            }
            headerStart={
                <Button variant="flat" asChild>
                    <Link href={backHref}>
                        <ArrowLeft />
                        {t("common.actions.back")}
                    </Link>
                </Button>
            }
            headerEnd={
                <PetDesktopHeaderActions
                    isOwner={isOwner}
                    canContactOwner={canContactOwner}
                    editHref={editHref}
                    editLabel={t("common.actions.edit")}
                    contactLabel={t("features.conversations.contactOwner")}
                    isContactPending={isContactPending}
                    onContact={onContact}
                />
            }
        />
    );
}

export default function PetDetailsPage() {
    const t = useTranslations();
    const { params, routes } = useNavigation<Query>();
    const { pet, isLoading } = usePet(params.id);
    const isMobile = useIsMobile();
    const { user, activities } = useAuth();
    const { openWithActivity, isPending: isContactPending } = useOpenConversation();

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
    const canContactOwner = !isOwner && activities.length > 0;
    const typeCode = pet.animalType?.code?.toLowerCase() ?? "";
    const images = pet.getGalleryImages();

    if (!isMobile) {
        return (
            <PetDetailDesktopView
                pet={pet}
                ageDisplay={ageDisplay}
                images={images}
                typeCode={typeCode}
                isOwner={isOwner}
                canContactOwner={canContactOwner}
                backHref={routes.MyPets()}
                editHref={routes.PetEditGeneral({ id: pet.id })}
                isContactPending={isContactPending}
                onContact={() => openWithActivity(activities[0]!.id, pet.userId)}
            />
        );
    }

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
                <PetHeaderActions
                    isOwner={isOwner}
                    canContactOwner={canContactOwner}
                    isMobile={isMobile}
                    mobileEditHref={routes.PetEditPage({ id: pet.id })}
                    desktopEditHref={routes.PetEditGeneral({ id: pet.id })}
                    editLabel={t("common.actions.edit")}
                    contactLabel={t("features.conversations.contactOwner")}
                    isContactPending={isContactPending}
                    onContact={() => openWithActivity(activities[0]!.id, pet.userId)}
                />
            }
            footer={
                isOwner ? (
                    <PetOwnerFooter
                        label={t("features.pets.profile.findHost", { name: pet.name })}
                    />
                ) : undefined
            }
            className="pb-6"
        >
            <PetProfileInfo pet={pet} ageDisplay={ageDisplay} />
        </DetailPageLayout>
    );
}
