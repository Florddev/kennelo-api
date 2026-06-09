"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import useEmblaCarousel from "embla-carousel-react";
import { cn } from "@workspace/ui/lib/utils";

export function PhotosCarousel({
    images,
    altPrefix,
    className,
}: {
    images: string[];
    altPrefix: string;
    className?: string;
}) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [emblaRef, emblaApi] = useEmblaCarousel({ loop: images.length > 1 });

    useEffect(() => {
        if (!emblaApi) return;
        const handleSelect = () => setCurrentIndex(emblaApi.selectedScrollSnap());
        handleSelect();
        emblaApi.on("select", handleSelect);
        emblaApi.on("reInit", handleSelect);
        return () => {
            emblaApi.off("select", handleSelect);
            emblaApi.off("reInit", handleSelect);
        };
    }, [emblaApi]);

    if (images.length === 0) return null;

    return (
        <div className={cn("relative aspect-[4/3] overflow-hidden bg-muted", className)}>
            <div ref={emblaRef} className="overflow-hidden h-full">
                <div className="flex h-full">
                    {images.map((url, i) => (
                        <div
                            key={`${url}-${i}`}
                            className="min-w-0 shrink-0 grow-0 basis-full h-full relative"
                        >
                            <Image
                                src={url}
                                alt={`${altPrefix} ${i + 1}`}
                                fill
                                className="object-cover"
                            />
                        </div>
                    ))}
                </div>
            </div>
            {images.length > 1 && (
                <div className="absolute inset-x-0 bottom-4 flex justify-center items-center gap-2">
                    {images.map((_, i) => (
                        <button
                            key={i}
                            type="button"
                            onClick={() => emblaApi?.scrollTo(i)}
                            className={cn(
                                "rounded-full transition-all",
                                i === currentIndex ? "w-6 h-2 bg-white" : "size-2 bg-white/40",
                            )}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
