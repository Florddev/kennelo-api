"use client";

import { useState } from "react";
import Image from "next/image";

import { useTranslations } from "next-intl";

import { Lightbox } from "@/components/media/lightbox";
import { PetSectionLabel } from "./pet-profile-info";

export function PetDesktopGallery({ name, images }: { name: string; images: string[] }) {
    const t = useTranslations();
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

    const totalCount = images.length;

    if (totalCount === 0) {
        return null;
    }

    const goNext = () => setLightboxIndex(((lightboxIndex ?? 0) + 1) % totalCount);
    const goPrev = () => setLightboxIndex(((lightboxIndex ?? 0) - 1 + totalCount) % totalCount);

    return (
        <div data-slot="pet-desktop-gallery" className="flex flex-col gap-4">
            <div className="flex flex-col">
                <PetSectionLabel title={t("features.pets.profile.galleryTitle", { name })} />
                <p className="text-muted-foreground">
                    {t("features.pets.profile.galleryDescription", { name })}
                </p>
            </div>

            <div className="grid grid-cols-2 gap-3 xl:grid-cols-3">
                {images.map((image, index) => (
                    <button
                        key={`${image}-${index}`}
                        type="button"
                        onClick={() => setLightboxIndex(index)}
                        className="group relative aspect-square overflow-hidden rounded-2xl"
                    >
                        <Image
                            src={image}
                            alt={`${name} ${index + 1}`}
                            fill
                            className="object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </button>
                ))}
            </div>

            {lightboxIndex !== null && (
                <Lightbox
                    images={images}
                    altPrefix={name}
                    currentIndex={lightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                    onNext={goNext}
                    onPrev={goPrev}
                    onGoTo={setLightboxIndex}
                />
            )}
        </div>
    );
}
