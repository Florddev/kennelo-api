"use client";

import { usePlatform } from "@/hooks/use-platform";
import { useScrolled } from "@/hooks/use-scrolled";
import { IconProps } from "@solar-icons/react";
import { cn } from "@workspace/ui/lib/utils";
export default function PageLayout({
    children,
    className,
    headerTopClassName,
    title,
    headerTop,
    headerBottom,
    hideTitle,
    Icon,
}: {
    children: React.ReactNode;
    className?: string;
    headerTopClassName?: string;
    title: string;
    headerTop?: React.ReactNode;
    headerBottom?: React.ReactNode;
    hideTitle?: boolean;
    Icon?: React.ComponentType<IconProps>;
}) {
    const scrolled = useScrolled(80);
    const { isCapacitorApp } = usePlatform();

    return (
        <div>
            <div
                className={cn(
                    "sticky top-0 md:static flex items-center z-10 bg-card",
                    scrolled && "border-b",
                )}
            >
                <div
                    className={cn(
                        "flex relative flex-col-reverse md:flex-row md:justify-between sm:items-start w-full py-2 p-4 sm:pt-6",
                        isCapacitorApp && "mt-[var(--mobile-top-margin)]",
                        scrolled && "py-2",
                    )}
                >
                    <div className={cn("flex flex-col gap-4", scrolled && "gap-3")}>
                        <h1
                            className={cn(
                                "flex gap-1.5 items-center tracking-tight transition-all sm:mt-0 h-8 font-heading",
                                scrolled || hideTitle
                                    ? "text-2xl font-semibold -mt-8"
                                    : "text-3xl font-bold",
                                hideTitle && "opacity-0",
                            )}
                        >
                            {Icon && (
                                <Icon
                                    weight="BoldDuotone"
                                    className={cn(
                                        "size-11 -ml-1.5 transition-all text-primary [&_*[opacity]]:opacity-100 [&_*[opacity]]:text-secondary",
                                        scrolled && "size-8",
                                    )}
                                />
                            )}
                            {title}
                        </h1>
                        {headerBottom}
                    </div>
                    <div
                        className={cn(
                            "ml-auto h-8 flex gap-1 items-center justify-between z-10",
                            headerTopClassName,
                        )}
                    >
                        {headerTop}
                    </div>
                </div>
            </div>

            <div className={cn("pt-3 pb-6 space-y-6 px-4", className)}>{children}</div>
        </div>
    );
}
