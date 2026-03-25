"use client";

import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";
import { useScroll } from "@/hooks/use-scroll";

import type { ActivePanel } from "../lib/types";
import { useScrolled } from "@/hooks/use-scrolled";

type SearchBarDatesProps = {
    activePanel: ActivePanel;
    isExpanded: boolean;
    dateDisplay: string | null;
    onClick: () => void;
};

export function SearchBarDates({
    activePanel,
    isExpanded,
    dateDisplay,
    onClick,
}: SearchBarDatesProps) {
    const t = useTranslations();
    const { scrollTo } = useScroll();

    const scrollValue = 300;
    const scrolled = useScrolled(scrollValue);

    function handleClick() {
        if (!scrolled) scrollTo({ top: scrollValue });
        onClick();
    }

    let activeClass = "";
    if (activePanel === "dates") {
        activeClass = "bg-secondary/10 ring-1 ring-secondary/25 shadow-sm";
    } else if (isExpanded) {
        activeClass = "hover:bg-muted/50";
    }

    return (
        <button
            data-slot="search-bar-dates"
            onClick={handleClick}
            className={cn(
                "flex-1 min-w-0 text-start px-6 py-2 rounded-full transition-all duration-150 outline-none",
                activeClass,
            )}
        >
            <div className="text-xs font-semibold mb-0.5">{t("features.search.dates")}</div>
            <div
                className={cn(
                    "text-sm truncate",
                    dateDisplay ? "text-foreground font-medium" : "text-muted-foreground",
                )}
            >
                {dateDisplay || t("features.search.when")}
            </div>
        </button>
    );
}
