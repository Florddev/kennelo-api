"use client";

import { useTranslations } from "next-intl";
import { PawPrint } from "lucide-react";
import { Button } from "@workspace/ui/components/button";

type WelcomeStepProps = {
    onNext: () => void;
};

export function WelcomeStep({ onNext }: WelcomeStepProps) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-10 py-8 md:py-16">
            <div className="flex items-center justify-center size-16 rounded-2xl bg-primary/10">
                <PawPrint className="size-8 text-primary" />
            </div>
            <div className="flex flex-col gap-3">
                <span className="text-sm font-semibold text-primary uppercase tracking-wide">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-4xl md:text-5xl font-bold tracking-tight leading-tight">
                    {t("features.become-host.steps.welcome.subtitle")}
                </h1>
            </div>
            <p className="text-lg text-muted-foreground max-w-md leading-relaxed">
                {t("features.become-host.steps.welcome.description")}
            </p>
            <div>
                <Button size="lg" className="rounded-4xl px-10 py-6 text-base" onClick={onNext}>
                    {t("features.become-host.steps.welcome.cta")}
                </Button>
            </div>
        </div>
    );
}
