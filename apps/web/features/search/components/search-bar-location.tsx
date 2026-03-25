"use client";

import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";
import { useScroll } from "@/hooks/use-scroll";

import type { ActivePanel } from "../lib/types";
import { useScrolled } from "@/hooks/use-scrolled";

type SearchBarLocationProps = {
    activePanel: ActivePanel;
    isExpanded: boolean;
    location: string;
    onChange: (value: string) => void;
    onFocus: () => void;
    onClear: () => void;
    inputRef: React.RefObject<HTMLInputElement | null>;
};

export function SearchBarLocation({
    activePanel,
    isExpanded,
    location,
    onChange,
    onFocus,
    onClear,
    inputRef,
}: SearchBarLocationProps) {
    const t = useTranslations();
    const { scrollTo } = useScroll();

    const scrollValue = 300;
    const scrolled = useScrolled(scrollValue);

    function handleFocus() {
        if (!scrolled) scrollTo({ top: scrollValue });
        onFocus();
    }

    let activeClass = "";
    if (activePanel === "location") {
        activeClass = "bg-secondary/10 ring-1 ring-secondary/25 shadow-sm";
    } else if (isExpanded) {
        activeClass = "hover:bg-muted/50";
    }

    return (
        <div
            data-slot="search-bar-location"
            onClick={handleFocus}
            className={cn(
                "flex-1 min-w-0 text-start px-6 py-2 rounded-full transition-all duration-150 cursor-text",
                activeClass,
            )}
        >
            <div className="text-xs font-semibold mb-0.5">{t("features.search.destination")}</div>
            <div className="flex items-center gap-1.5">
                <input
                    ref={inputRef}
                    value={location}
                    onChange={(e) => onChange(e.target.value)}
                    onFocus={handleFocus}
                    placeholder={t("common.placeholders.search-for-a-pension")}
                    className="flex-1 min-w-0 bg-transparent outline-none text-sm placeholder:text-muted-foreground"
                />
                {location && activePanel === "location" && (
                    <button
                        onPointerDown={(e) => {
                            e.preventDefault();
                            onClear();
                        }}
                        className="shrink-0 size-4 rounded-full bg-foreground/15 hover:bg-foreground/25 flex items-center justify-center transition-colors"
                    >
                        <span className="text-[9px] leading-none">✕</span>
                    </button>
                )}
            </div>
        </div>
    );
}
