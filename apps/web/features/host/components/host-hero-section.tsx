"use client";

import { ArrowLeft, Image as ImageIcon } from "lucide-react";
import { useTranslations } from "next-intl";
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { MediaGallery } from "@/components/media/media-gallery";

type HostHeroSectionProps = {
    images: string[];
    name: string;
    onBack: () => void;
};

export function HostHeroSection({ images, name, onBack }: HostHeroSectionProps) {
    const t = useTranslations();
    const emptyState = (
        <div className="relative aspect-[4/3] w-full overflow-hidden bg-muted">
            <Empty className="h-full border-0">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <ImageIcon />
                    </EmptyMedia>
                    <EmptyTitle>{t("features.host.detail.noPhotos")}</EmptyTitle>
                </EmptyHeader>
            </Empty>
        </div>
    );

    return (
        <div className="relative">
            <MediaGallery images={images} altPrefix={name} emptyState={emptyState} />
            <button
                type="button"
                onClick={onBack}
                aria-label="Back"
                className="absolute top-4 start-4 z-10 flex size-10 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm"
            >
                <ArrowLeft className="size-4" />
            </button>
        </div>
    );
}
