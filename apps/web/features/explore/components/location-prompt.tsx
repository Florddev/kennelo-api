"use client";

import { useState } from "react";
import { MapPin, X } from "lucide-react";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { useGeolocation } from "@/hooks/use-geolocation";
import { useLocation } from "@/features/explore/context/location-context";

type LocationPromptProps = {
    className?: string;
};

export function LocationPrompt({ className }: LocationPromptProps) {
    const t = useTranslations();
    const { requestPosition } = useGeolocation();
    const { setCoords, setError, dismiss } = useLocation();
    const [isLoading, setIsLoading] = useState(false);

    async function handleActivate() {
        setIsLoading(true);
        const result = await requestPosition();
        setIsLoading(false);

        if (result.error) {
            setError(result.error);
            dismiss();
        } else {
            setCoords(result.coords);
        }
    }

    return (
        <div
            data-slot="location-prompt"
            className={cn(
                "mx-4 flex items-center gap-3 rounded-2xl bg-secondary/10 border border-secondary/20 px-4 py-3",
                className,
            )}
        >
            <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary/20">
                <MapPin className="size-4 text-secondary" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold leading-tight">
                    {t("features.explore.location.promptTitle")}
                </p>
                <p className="text-xs text-muted-foreground leading-tight mt-0.5">
                    {t("features.explore.location.promptDescription")}
                </p>
            </div>
            <button
                type="button"
                onClick={handleActivate}
                disabled={isLoading}
                className="shrink-0 rounded-full bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground transition-opacity disabled:opacity-60"
            >
                {isLoading ? "…" : t("features.explore.location.activate")}
            </button>
            <button
                type="button"
                onClick={dismiss}
                aria-label={t("features.explore.location.dismiss")}
                className="shrink-0 size-6 flex items-center justify-center rounded-full hover:bg-muted transition-colors"
            >
                <X className="size-3.5 text-muted-foreground" />
            </button>
        </div>
    );
}
