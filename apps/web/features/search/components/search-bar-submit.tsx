"use client";

import { useTranslations } from "next-intl";
import { Search } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";

type SearchBarSubmitProps = {
    isExpanded: boolean;
    onClick?: () => void;
};

export function SearchBarSubmit({ isExpanded, onClick }: SearchBarSubmitProps) {
    const t = useTranslations();

    return (
        <div className="pe-1.5 shrink-0">
            <Button
                data-slot="search-bar-submit"
                onClick={onClick}
                className={cn(
                    "rounded-full transition-all duration-200 shadow-sm",
                    isExpanded ? "px-6 gap-2 h-10" : "size-10 p-0",
                )}
                size={isExpanded ? "default" : "icon"}
            >
                <Search size={16} />
                {isExpanded && <span className="font-semibold">{t("features.search.cta")}</span>}
            </Button>
        </div>
    );
}
