"use client";

import { useTranslations } from "next-intl";

export function WelcomeStep() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-10 h-full justify-center">
            <div className="flex flex-col gap-4 max-w-xl">
                <span className="text-base font-semibold text-primary">
                    {t("features.become-host.steps.welcome.title")}
                </span>
                <h1 className="text-4xl md:text-5xl font-semibold tracking-tight max-w-md">
                    {t("features.become-host.steps.welcome.subtitle")}
                </h1>
                <p className="text-lg text-muted-foreground max-w-xl">
                    {t("features.become-host.steps.welcome.description")}
                </p>
            </div>
        </div>
    );
}
