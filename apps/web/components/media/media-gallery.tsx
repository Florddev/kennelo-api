"use client";

import Image from "next/image";
import { Images, X } from "lucide-react";
import { ReactNode, useEffect, useState } from "react";
import useEmblaCarousel from "embla-carousel-react";
import { Lightbox } from "@/components/media/lightbox";
import { useIsMobile } from "@/hooks/use-mobile";

type MediaGalleryProps = {
    images: string[];
    altPrefix: string;
    emptyState?: ReactNode;
    desktopCtaLabel?: string;
};

type MobileSwipeLightboxProps = {
    images: string[];
    altPrefix: string;
    currentIndex: number;
    onClose: () => void;
    onGoTo: (index: number) => void;
};

function GalleryIndicators({
    count,
    currentIndex,
    onGoTo,
}: {
    count: number;
    currentIndex: number;
    onGoTo: (index: number) => void;
}) {
    if (count <= 1) return null;

    return (
        <div className="flex items-center justify-center gap-2">
            {Array.from({ length: count }).map((_, i) => (
                <button
                    key={i}
                    type="button"
                    onClick={() => onGoTo(i)}
                    className={`rounded-full transition-all ${
                        i === currentIndex ? "w-6 h-2 bg-white" : "size-2 bg-white/40"
                    }`}
                />
            ))}
        </div>
    );
}

function MobileSwipeLightbox({
    images,
    altPrefix,
    currentIndex,
    onClose,
    onGoTo,
}: MobileSwipeLightboxProps) {
    const [emblaRef, emblaApi] = useEmblaCarousel({
        axis: "x",
        loop: images.length > 1,
        startIndex: currentIndex,
    });

    useEffect(() => {
        document.body.style.overflow = "hidden";
        return () => {
            document.body.style.overflow = "";
        };
    }, []);

    useEffect(() => {
        if (!emblaApi) return;
        emblaApi.scrollTo(currentIndex, true);
    }, [emblaApi, currentIndex]);

    useEffect(() => {
        if (!emblaApi) return;

        const handleSelect = () => {
            onGoTo(emblaApi.selectedScrollSnap());
        };

        handleSelect();
        emblaApi.on("select", handleSelect);
        emblaApi.on("reInit", handleSelect);

        return () => {
            emblaApi.off("select", handleSelect);
            emblaApi.off("reInit", handleSelect);
        };
    }, [emblaApi, onGoTo]);

    return (
        <div className="fixed inset-0 z-50 bg-black/95 flex flex-col" onClick={onClose}>
            <button
                type="button"
                className="absolute top-4 end-4 size-10 rounded-full bg-neutral-800 hover:bg-neutral-700 transition-colors flex items-center justify-center z-10"
                onClick={(e) => {
                    e.stopPropagation();
                    onClose();
                }}
            >
                <X className="size-5 text-white" />
            </button>

            <div className="flex-1 flex items-center" onClick={(e) => e.stopPropagation()}>
                <div ref={emblaRef} className="overflow-hidden w-full">
                    <div className="flex">
                        {images.map((image, index) => (
                            <div
                                key={`${image}-${index}`}
                                className="min-w-0 shrink-0 grow-0 basis-full"
                            >
                                <div className="relative w-full h-[70vh]">
                                    <Image
                                        src={image}
                                        alt={`${altPrefix} ${index + 1}`}
                                        fill
                                        className="object-contain"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <div className="pb-6" onClick={(e) => e.stopPropagation()}>
                <GalleryIndicators
                    count={images.length}
                    currentIndex={currentIndex}
                    onGoTo={onGoTo}
                />
            </div>
        </div>
    );
}

function DesktopGallery({
    images,
    altPrefix,
    desktopCtaLabel,
    lightboxIndex,
    setLightboxIndex,
}: {
    images: string[];
    altPrefix: string;
    desktopCtaLabel?: string;
    lightboxIndex: number | null;
    setLightboxIndex: (index: number | null) => void;
}) {
    const totalCount = images.length;

    const goNext = () => setLightboxIndex(((lightboxIndex ?? 0) + 1) % totalCount);
    const goPrev = () => setLightboxIndex(((lightboxIndex ?? 0) - 1 + totalCount) % totalCount);

    if (totalCount === 1) {
        return (
            <>
                <div
                    className="relative aspect-[16/10] rounded-2xl overflow-hidden cursor-pointer"
                    onClick={() => setLightboxIndex(0)}
                >
                    <Image src={images[0]!} alt={altPrefix} fill className="object-cover" />
                </div>
                {lightboxIndex !== null && (
                    <Lightbox
                        images={images}
                        altPrefix={altPrefix}
                        currentIndex={lightboxIndex}
                        onClose={() => setLightboxIndex(null)}
                        onNext={goNext}
                        onPrev={goPrev}
                        onGoTo={setLightboxIndex}
                    />
                )}
            </>
        );
    }

    const [main, ...rest] = images;
    const displayThumbs = rest.slice(0, 2);

    return (
        <>
            <div className="relative aspect-[16/10] rounded-2xl overflow-hidden">
                <div className="grid h-full" style={{ gridTemplateColumns: "3fr 2fr", gap: "3px" }}>
                    <div
                        className="relative overflow-hidden cursor-pointer"
                        onClick={() => setLightboxIndex(0)}
                    >
                        <Image src={main!} alt={altPrefix} fill className="object-cover" />
                    </div>
                    <div className="flex flex-col gap-[3px]">
                        {displayThumbs.map((url, i) => (
                            <div
                                key={url}
                                className="relative overflow-hidden flex-1 cursor-pointer"
                                onClick={() => setLightboxIndex(i + 1)}
                            >
                                <Image src={url} alt={altPrefix} fill className="object-cover" />
                            </div>
                        ))}
                        {displayThumbs.length < 2 && <div className="flex-1 bg-muted" />}
                    </div>
                </div>
                {desktopCtaLabel && (
                    <button
                        type="button"
                        className="absolute bottom-3 end-3 flex items-center gap-1.5 bg-background/90 backdrop-blur-sm text-xs font-medium px-3 py-1.5 rounded-4xl border shadow-sm hover:bg-background transition-colors"
                        onClick={() => setLightboxIndex(0)}
                    >
                        <Images className="size-3.5" />
                        {desktopCtaLabel}
                    </button>
                )}
            </div>
            {lightboxIndex !== null && (
                <Lightbox
                    images={images}
                    altPrefix={altPrefix}
                    currentIndex={lightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                    onNext={goNext}
                    onPrev={goPrev}
                    onGoTo={setLightboxIndex}
                />
            )}
        </>
    );
}

function MobileGallery({
    images,
    altPrefix,
    lightboxIndex,
    setLightboxIndex,
}: {
    images: string[];
    altPrefix: string;
    lightboxIndex: number | null;
    setLightboxIndex: (index: number | null) => void;
}) {
    const [carouselIndex, setCarouselIndex] = useState(0);

    const [emblaRef, emblaApi] = useEmblaCarousel({
        axis: "x",
        loop: images.length > 1,
    });

    useEffect(() => {
        if (!emblaApi) return;

        const handleSelect = () => {
            setCarouselIndex(emblaApi.selectedScrollSnap());
        };

        handleSelect();
        emblaApi.on("select", handleSelect);
        emblaApi.on("reInit", handleSelect);

        return () => {
            emblaApi.off("select", handleSelect);
            emblaApi.off("reInit", handleSelect);
        };
    }, [emblaApi]);

    return (
        <>
            <div className="relative w-full h-[50vh] overflow-hidden">
                <div ref={emblaRef} className="overflow-hidden h-full">
                    <div className="flex h-full bg-muted">
                        {images.map((image, index) => (
                            <div
                                key={`${image}-${index}`}
                                className="min-w-0 shrink-0 grow-0 basis-full h-full"
                            >
                                <button
                                    type="button"
                                    className="relative h-full w-full"
                                    onClick={() => setLightboxIndex(index)}
                                >
                                    <Image
                                        src={image}
                                        alt={altPrefix}
                                        fill
                                        className="object-cover"
                                    />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="absolute bottom-6 left-1/2 -translate-x-1/2 h-8 flex items-center justify-center">
                    <GalleryIndicators
                        count={images.length}
                        currentIndex={carouselIndex}
                        onGoTo={(index) => emblaApi?.scrollTo(index)}
                    />
                </div>
            </div>

            {lightboxIndex !== null && (
                <MobileSwipeLightbox
                    images={images}
                    altPrefix={altPrefix}
                    currentIndex={lightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                    onGoTo={setLightboxIndex}
                />
            )}
        </>
    );
}

export function MediaGallery({
    images,
    altPrefix,
    emptyState,
    desktopCtaLabel,
}: MediaGalleryProps) {
    const isMobile = useIsMobile();
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

    if (images.length === 0) {
        return <>{emptyState ?? null}</>;
    }

    if (isMobile) {
        return (
            <MobileGallery
                images={images}
                altPrefix={altPrefix}
                lightboxIndex={lightboxIndex}
                setLightboxIndex={setLightboxIndex}
            />
        );
    }

    return (
        <DesktopGallery
            images={images}
            altPrefix={altPrefix}
            desktopCtaLabel={desktopCtaLabel}
            lightboxIndex={lightboxIndex}
            setLightboxIndex={setLightboxIndex}
        />
    );
}
