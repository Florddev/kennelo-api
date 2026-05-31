"use client";

import React, { createContext, useContext } from "react";

import { Separator } from "@workspace/ui/components/separator";
import { cn } from "@workspace/ui/lib/utils";

import { useHideBottomNavbar } from "@/hooks/use-hide-bottom-navbar";
import { useScrolled } from "@/hooks/use-scrolled";
import { usePlatform } from "@/hooks/use-platform";

export type SplitPageLayoutNavItem = {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    href: string;
    comingSoon?: boolean;
    default?: boolean;
};

type SplitPageLayoutContextValue = {
    isRoot: boolean;
};

const SplitPageLayoutContext = createContext<SplitPageLayoutContextValue | null>(null);

function useSplitPageLayout() {
    const ctx = useContext(SplitPageLayoutContext);
    if (!ctx) throw new Error("Must be used inside SplitPageLayout");
    return ctx;
}

function SplitPageLayout({
    isRoot,
    children,
    className,
}: {
    isRoot: boolean;
    children: React.ReactNode;
    className?: string;
}) {
    useHideBottomNavbar();
    const { isCapacitorApp } = usePlatform();

    const childrenArray = React.Children.toArray(children);
    const sidebar = childrenArray.find(
        (child) => React.isValidElement(child) && child.type === SplitPageLayoutSidebar,
    );
    const content = childrenArray.find(
        (child) => React.isValidElement(child) && child.type === SplitPageLayoutContent,
    );

    return (
        <SplitPageLayoutContext.Provider value={{ isRoot }}>
            <div
                data-slot="split-page-layout"
                className={cn(
                    "flex flex-col md:flex-row w-full justify-between",
                    "h-fit md:h-[calc(100dvh-var(--header-height))] md:overflow-hidden",
                    isCapacitorApp && "pt-[var(--mobile-top-margin)]",
                    className,
                )}
            >
                {sidebar}
                <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />
                {content}
            </div>
        </SplitPageLayoutContext.Provider>
    );
}

function SplitPageLayoutSidebar({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div
            data-slot="split-page-layout-sidebar"
            className={cn("w-full md:w-1/3 md:p-8 md:overflow-y-auto", className)}
        >
            <div className="flex flex-col">{children}</div>
        </div>
    );
}

function SplitPageLayoutHeader({
    children,
    mobileOnly = false,
    className,
}: {
    children: React.ReactNode;
    mobileOnly?: boolean;
    className?: string;
}) {
    const scrolled = useScrolled(100);

    return (
        <>
            <div
                data-slot="split-page-layout-header"
                className={cn(
                    "bg-card fixed top-0 z-10 w-full",
                    scrolled && "border-b",
                    mobileOnly ? "md:hidden" : "md:static md:border-b-0",
                    className,
                )}
            >
                <div
                    className={cn(
                        "flex items-center gap-3 px-4 py-2 w-full",
                        !mobileOnly && "md:p-0 md:pb-4",
                    )}
                >
                    {children}
                </div>
            </div>
            <div className={mobileOnly ? "pt-13 md:pt-0 px-4 md:px-0" : "mt-13 md:mt-0 md:px-0"} />
        </>
    );
}

function SplitPageLayoutNav({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    const { isRoot } = useSplitPageLayout();

    return (
        <div
            data-slot="split-page-layout-nav"
            className={cn("flex flex-col gap-2", !isRoot && "hidden md:flex")}
        >
            <div className={cn("flex flex-col gap-2 px-4 py-2 md:px-0 md:py-4 w-full", className)}>
                {children}
            </div>
        </div>
    );
}

function SplitPageLayoutContent({
    children,
    sectionTitle,
    defaultContent,
    className,
    cleanContainer = false,
}: {
    children: React.ReactNode;
    sectionTitle?: string;
    defaultContent?: React.ReactNode;
    className?: string;
    cleanContainer?: boolean;
}) {
    const { isRoot } = useSplitPageLayout();

    return (
        <div
            data-slot="split-page-layout-content"
            className={cn("md:w-2/3 md:p-8 md:overflow-y-auto h-full", className)}
        >
            <div className="flex-1">
                <div
                    className={cn(
                        !cleanContainer && "flex flex-col gap-3 pb-8",
                        isRoot && "hidden md:block",
                        !cleanContainer && !isRoot && "px-4 md:p-0",
                    )}
                >
                    {sectionTitle && !isRoot && (
                        <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                            {sectionTitle}
                        </h1>
                    )}
                    {isRoot ? defaultContent : children}
                </div>
            </div>
        </div>
    );
}

SplitPageLayout.Sidebar = SplitPageLayoutSidebar;
SplitPageLayout.Header = SplitPageLayoutHeader;
SplitPageLayout.Nav = SplitPageLayoutNav;
SplitPageLayout.Content = SplitPageLayoutContent;

export { SplitPageLayout };
