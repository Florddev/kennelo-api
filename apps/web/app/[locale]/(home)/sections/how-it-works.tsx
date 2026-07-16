"use client";

import { useTranslations } from "next-intl";

import { KCalendar, KCompass, KHeart } from "@workspace/ui/icons";

const STEPS = [
    { key: "search", Icon: KCompass },
    { key: "book", Icon: KCalendar },
    { key: "enjoy", Icon: KHeart },
] as const;

export default function HowItWorks() {
    const t = useTranslations();

    return (
        <section data-slot="how-it-works" className="flex flex-col gap-8">
            <div className="flex flex-col gap-2 text-center">
                <h2 className="text-4xl font-bold tracking-tight">
                    {t("features.home.how-it-works.title")}
                </h2>
                <p className="text-muted-foreground text-sm">
                    {t("features.home.how-it-works.subtitle")}
                </p>
            </div>
            <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                {STEPS.map(({ key, Icon }) => (
                    <div key={key} className="flex flex-col items-center gap-3 text-center">
                        <span className="flex size-14 items-center justify-center rounded-2xl bg-secondary/15">
                            <Icon className="size-7 text-primary" />
                        </span>
                        <h3 className="font-semibold">
                            {t(`features.home.how-it-works.${key}.title`)}
                        </h3>
                        <p className="max-w-xs text-sm text-muted-foreground">
                            {t(`features.home.how-it-works.${key}.description`)}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}
