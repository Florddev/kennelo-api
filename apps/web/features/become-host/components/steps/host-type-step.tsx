"use client";

import { useTranslations } from "next-intl";
import { Building2, User, AlertCircle } from "lucide-react";
import { cn } from "@workspace/ui/lib/utils";

type HostType = "professional" | "individual";

type HostTypeStepProps = {
    value: HostType | null;
    onChange: (type: HostType) => void;
    error?: string;
};

const HOST_TYPE_OPTIONS: { value: HostType; icon: typeof Building2 }[] = [
    { value: "professional", icon: Building2 },
    { value: "individual", icon: User },
];

export function HostTypeStep({ value, onChange, error }: HostTypeStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-col gap-2">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-3xl md:text-4xl font-bold tracking-tight">
                    {t("features.become-host.steps.hostType.title")}
                </h1>
                <p className="text-lg text-muted-foreground">
                    {t("features.become-host.steps.hostType.subtitle")}
                </p>
            </div>
            {error && (
                <div className="flex items-center gap-2 rounded-2xl border border-destructive/30 bg-destructive/5 px-4 py-3">
                    <AlertCircle className="size-4 text-destructive shrink-0" />
                    <p className="text-sm text-destructive font-medium">{error}</p>
                </div>
            )}
            <div className="flex flex-col gap-4">
                {HOST_TYPE_OPTIONS.map((option) => {
                    const Icon = option.icon;
                    const isSelected = value === option.value;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => onChange(option.value)}
                            className={cn(
                                "flex items-start gap-4 rounded-2xl border-2 p-5 text-start transition-all",
                                isSelected
                                    ? "border-primary bg-primary/5"
                                    : "border-border hover:border-primary/40 hover:bg-muted/50",
                            )}
                        >
                            <div
                                className={cn(
                                    "flex items-center justify-center size-12 rounded-2xl shrink-0",
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
                            <div className="flex flex-col gap-1">
                                <span className="font-semibold text-lg">
                                    {t(`features.become-host.steps.hostType.${option.value}`)}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {t(
                                        `features.become-host.steps.hostType.${option.value}Description`,
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
