"use client";

import { useTranslations } from "next-intl";
import { Home, Heart, Sun, Shield, MoreHorizontal, AlertCircle } from "lucide-react";
import { cn } from "@workspace/ui/lib/utils";

export type EstablishmentType = "pension" | "breeding" | "daycare" | "shelter" | "other";

type EstablishmentTypeStepProps = {
    value: EstablishmentType | null;
    onChange: (type: EstablishmentType) => void;
    error?: string;
};

const ESTABLISHMENT_TYPES: { value: EstablishmentType; icon: typeof Home }[] = [
    { value: "pension", icon: Home },
    { value: "breeding", icon: Heart },
    { value: "daycare", icon: Sun },
    { value: "shelter", icon: Shield },
    { value: "other", icon: MoreHorizontal },
];

export function EstablishmentTypeStep({ value, onChange, error }: EstablishmentTypeStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.establishmentType.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.establishmentType.subtitle")}
                </p>
            </div>
            {error && (
                <div className="flex items-center gap-2 rounded-2xl border border-destructive/30 bg-destructive/5 px-4 py-3">
                    <AlertCircle className="size-4 text-destructive shrink-0" />
                    <p className="text-sm text-destructive font-medium">{error}</p>
                </div>
            )}
            <div className="grid grid-cols-2 gap-3">
                {ESTABLISHMENT_TYPES.map((option) => {
                    const Icon = option.icon;
                    const isSelected = value === option.value;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => onChange(option.value)}
                            className={cn(
                                "flex flex-col items-center gap-3 rounded-2xl border-2 p-5 text-center transition-all",
                                isSelected
                                    ? "border-primary bg-primary/5"
                                    : "border-border hover:border-primary/40 hover:bg-muted/50",
                                option.value === "other" && "col-span-2",
                            )}
                        >
                            <div
                                className={cn(
                                    "flex items-center justify-center size-12 rounded-2xl",
                                    isSelected ? "bg-primary/10" : "bg-muted",
                                )}
                            >
                                <Icon
                                    className={cn(
                                        "size-6",
                                        isSelected ? "text-primary" : "text-muted-foreground",
                                    )}
                                />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="font-semibold">
                                    {t(
                                        `features.become-host.steps.establishmentType.${option.value}`,
                                    )}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {t(
                                        `features.become-host.steps.establishmentType.${option.value}Description`,
                                    )}
                                </span>
                            </div>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
