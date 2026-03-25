"use client";

import Image from "next/image";
import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";
import { useScroll } from "@/hooks/use-scroll";

import type { ActivePanel, PetCounts, PetType } from "../lib/types";
import { useScrolled } from "@/hooks/use-scrolled";

type SearchBarPetsProps = {
    activePanel: ActivePanel;
    isExpanded: boolean;
    totalPets: number;
    petCounts: PetCounts;
    selectedPetTypes: PetType[];
    onClick: () => void;
};

export function SearchBarPets({
    activePanel,
    isExpanded,
    totalPets,
    selectedPetTypes,
    onClick,
}: SearchBarPetsProps) {
    const t = useTranslations();
    const { scrollTo } = useScroll();

    const scrollValue = 300;
    const scrolled = useScrolled(scrollValue);

    function handleClick() {
        if (!scrolled) scrollTo({ top: scrollValue });
        onClick();
    }

    let activeClass = "";
    if (activePanel === "pets") {
        activeClass = "bg-secondary/10 ring-1 ring-secondary/25 shadow-sm";
    } else if (isExpanded) {
        activeClass = "hover:bg-muted/50";
    }

    return (
        <button
            data-slot="search-bar-pets"
            onClick={handleClick}
            className={cn(
                "flex-[0.9] min-w-0 text-start px-6 py-2 rounded-full transition-all duration-150 outline-none",
                activeClass,
            )}
        >
            <div className="text-xs font-semibold mb-0.5">{t("features.search.pets.label")}</div>
            {selectedPetTypes.length > 0 ? (
                <div className="flex items-center gap-1.5">
                    <div className="flex items-center -space-x-1">
                        {selectedPetTypes.slice(0, 3).map((type) => (
                            <div
                                key={type}
                                className="size-6 rounded-full bg-secondary/20 ring-1 ring-card flex items-center justify-center"
                            >
                                <Image
                                    src={`/illustrations/pets/${type}.svg`}
                                    width={16}
                                    height={16}
                                    alt={t(`features.search.pets.${type}`)}
                                />
                            </div>
                        ))}
                    </div>
                    <span className="text-sm text-foreground font-medium truncate">
                        {t("features.search.pets.count", { count: totalPets })}
                    </span>
                </div>
            ) : (
                <div className="text-sm text-muted-foreground truncate">
                    {t("features.search.pets.add")}
                </div>
            )}
        </button>
    );
}
