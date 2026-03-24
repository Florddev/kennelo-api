"use client";

import { useTranslations } from "next-intl";

export function WelcomeStep() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-10 h-full justify-center">
            <div className="flex flex-col gap-3 max-w-3xl">
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
        </div>
    );
}
