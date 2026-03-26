"use client";

import Image from "next/image";
import { PawPrint } from "lucide-react";
import { useTranslations } from "next-intl";
import type { PetModel } from "@workspace/modules/pets";
import { MediaGallery } from "@/components/media/media-gallery";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

type PetGalleryProps = {
    pet: PetModel;
};

function buildAllImages(avatarUrl: string | null, images: { url: string }[]): string[] {
    const list = images.map((img) => img.url);
    if (avatarUrl) return [avatarUrl, ...list];
    return list;
}

function PetGalleryEmpty({ typeCode, altName }: { typeCode: string; altName: string }) {
    if (isIllustratedType(typeCode)) {
        return (
            <div className="relative aspect-[16/10] rounded-2xl overflow-hidden bg-muted">
                <Image
                    src={`/illustrations/pets/${typeCode}.svg`}
                    alt={altName}
                    fill
                    className="object-contain p-12"
                />
            </div>
        );
    }
    return (
        <div className="relative aspect-[16/10] rounded-2xl overflow-hidden bg-muted">
            <div className="absolute inset-0 flex items-center justify-center">
                <PawPrint className="size-20 text-muted-foreground/15" />
            </div>
        </div>
    );
}

export function PetGallery({ pet }: PetGalleryProps) {
    const t = useTranslations();
    const typeCode = pet.animalType?.code?.toLowerCase() ?? "";
    const allImages = buildAllImages(pet.avatarUrl, pet.images);

    return (
        <MediaGallery
            images={allImages}
            altPrefix={pet.name}
            emptyState={
                <PetGalleryEmpty typeCode={typeCode} altName={pet.animalType?.name ?? ""} />
            }
            desktopCtaLabel={t("features.pets.profile.viewPhotos", { count: allImages.length })}
        />
    );
}
