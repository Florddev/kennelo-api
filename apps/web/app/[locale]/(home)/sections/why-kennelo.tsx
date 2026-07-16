"use client";

import { useTranslations } from "next-intl";
import { HeartHandshake, Lock, ShieldCheck, Users } from "lucide-react";

import { Card, CardContent } from "@workspace/ui/components/card";

const VALUES = [
    { key: "verified", Icon: ShieldCheck },
    { key: "care", Icon: HeartHandshake },
    { key: "community", Icon: Users },
    { key: "secure", Icon: Lock },
] as const;

export default function WhyKennelo() {
    const t = useTranslations();

    return (
        <section
            data-slot="why-kennelo"
            className="relative overflow-hidden rounded-3xl bg-secondary/15 px-6 py-14 sm:px-10"
        >
            <span
                aria-hidden
                className="pointer-events-none absolute -top-12 -end-12 size-48 rounded-full bg-secondary"
            />
            <span
                aria-hidden
                className="pointer-events-none absolute -bottom-16 -start-10 size-56 rounded-full bg-secondary"
            />
            <div className="relative flex flex-col gap-8">
                <div className="flex flex-col gap-2 text-center">
                    <h2 className="text-4xl font-bold tracking-tight">
                        {t("features.home.why.title")}
                    </h2>
                    <p className="text-sm text-foreground/70">{t("features.home.why.subtitle")}</p>
                </div>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {VALUES.map(({ key, Icon }) => (
                        <Card key={key} className="gap-3 bg-card py-5">
                            <CardContent className="flex flex-col gap-3">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-secondary/30">
                                    <Icon className="size-5 text-primary" />
                                </span>
                                <div className="flex flex-col gap-1">
                                    <h3 className="text-sm font-semibold">
                                        {t(`features.home.why.${key}.title`)}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {t(`features.home.why.${key}.description`)}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
