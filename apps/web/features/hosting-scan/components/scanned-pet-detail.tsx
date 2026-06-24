"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { ArrowLeft } from "@solar-icons/react";

import type { BookingModel } from "@workspace/modules/bookings";
import type { PetModel } from "@workspace/modules/pets";
import type { ScanOwnerModel } from "@workspace/modules/scanners";
import { Button } from "@workspace/ui/components/button";

import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import { PetImageEmptyState } from "@/features/pets/components/pet-image-empty-state";
import { formatAgeDisplay } from "@/features/pets/lib/pet-age";
import { useNavigation } from "@/hooks/use-navigation";
import { ContactOwnerButton } from "./contact-owner-button";
import { ScannedPetProfile } from "./scanned-pet-profile";

export function ScannedPetDetail({
    pet,
    owner,
    currentBooking,
    pastBookings,
}: {
    pet: PetModel;
    owner: ScanOwnerModel | null;
    currentBooking: BookingModel | null;
    pastBookings: BookingModel[];
}) {
    const t = useTranslations();
    const { routes } = useNavigation();

    const ageDisplay = formatAgeDisplay(
        pet.birthDate,
        (count) => t("features.pets.age.years", { count }),
        (count) => t("features.pets.age.months", { count }),
    );

    const images = pet.getGalleryImages();
    const typeCode = pet.animalType?.code?.toLowerCase() ?? "";

    const contactBookingId =
        owner !== null ? (currentBooking?.id ?? pastBookings[0]?.id ?? null) : null;

    return (
        <DetailPageLayout
            images={images}
            altPrefix={pet.name}
            emptyState={
                <PetImageEmptyState typeCode={typeCode} typeName={pet.animalType?.name ?? ""} />
            }
            desktopCtaLabel={t("features.pets.profile.viewPhotos", { count: images.length })}
            headerStart={
                <Button size="icon-sm" className="bg-card text-primary hover:bg-muted" asChild>
                    <Link href={routes.HostingScan()}>
                        <ArrowLeft />
                    </Link>
                </Button>
            }
            headerEnd={
                contactBookingId ? (
                    <ContactOwnerButton
                        bookingId={contactBookingId}
                        size="sm"
                        className="gap-1.5 bg-card text-primary hover:bg-muted"
                    />
                ) : undefined
            }
            footer={
                contactBookingId ? (
                    <div className="flex h-16 items-center justify-center border-t bg-card px-2 sm:hidden">
                        <ContactOwnerButton
                            bookingId={contactBookingId}
                            size="xl"
                            className="w-full"
                        />
                    </div>
                ) : undefined
            }
            className="pb-6"
        >
            <ScannedPetProfile
                pet={pet}
                owner={owner}
                ageDisplay={ageDisplay}
                currentBooking={currentBooking}
                pastBookings={pastBookings}
            />
        </DetailPageLayout>
    );
}
