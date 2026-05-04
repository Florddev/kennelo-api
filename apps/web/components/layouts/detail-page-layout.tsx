"use client";

import { ReactNode } from "react";
import { cn } from "@workspace/ui/lib/utils";
import { MediaGallery } from "@/components/media/media-gallery";

type DetailPageLayoutProps = {
    images: string[];
    altPrefix: string;
    emptyState?: ReactNode;
    desktopCtaLabel?: string;
    headerStart?: ReactNode;
    headerEnd?: ReactNode;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
};

export function DetailPageLayout({
    images,
    altPrefix,
    emptyState,
    desktopCtaLabel,
    headerStart,
    headerEnd,
    footer,
    children,
    className,
}: DetailPageLayoutProps) {
    return (
        <div data-slot="detail-page-layout" className={cn("relative min-h-screen", className)}>
            <div className="absolute top-0 start-0 end-0 z-10 sm:static sm:z-auto flex justify-between items-center p-2">
                <div>{headerStart}</div>
                <div className="flex gap-0.5">{headerEnd}</div>
            </div>
            <MediaGallery
                images={images}
                altPrefix={altPrefix}
                emptyState={emptyState}
                desktopCtaLabel={desktopCtaLabel}
            />
            <div className="relative -mt-6 z-10 bg-card rounded-t-[32px] sm:mt-0 sm:rounded-none">
                {children}
            </div>
            {footer && <div className="fixed inset-x-0 bottom-0 z-20">{footer}</div>}
        </div>
    );
}
