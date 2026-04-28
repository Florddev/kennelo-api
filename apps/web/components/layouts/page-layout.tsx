"use client";

import { useScrolled } from "@/hooks/use-scrolled";
import { KIcon } from "@workspace/ui/icons";
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
    Icon?: KIcon;
}) {
    const scrolled = useScrolled(80);

    return (
        <div>
            <div
                className={cn(
                    "sticky top-0 flex items-center z-10 bg-card",
                    scrolled && "border-b",
                )}
            >
                <div className="flex flex-col-reverse md:flex-row md:justify-between sm:items-start w-full py-2 p-4 sm:pt-6">
                    <div className={cn("flex flex-col gap-4", scrolled && "gap-3")}>
                        <h1
                            className={cn(
                                "flex gap-1.5 items-center tracking-tight transition-all sm:mt-0 h-8",
                                scrolled || hideTitle
                                    ? "text-xl font-semibold -mt-8"
                                    : "text-3xl font-bold mt-4",
                                hideTitle && "opacity-0",
                            )}
                        >
                            {Icon && (
                                <Icon
                                    className={cn(
                                        "size-11 -ml-1.5 transition-all",
                                        scrolled && "size-8",
                                    )}
                                    filled
                                    secondaryOpacity={1}
                                    secondary="text-secondary"
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
