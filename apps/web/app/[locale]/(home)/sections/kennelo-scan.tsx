"use client";

import { useTranslations } from "next-intl";
import { Bluetooth, History, ScanLine } from "lucide-react";

const FEATURES = [
    { key: "instant", Icon: ScanLine },
    { key: "wireless", Icon: Bluetooth },
    { key: "tracking", Icon: History },
] as const;

export default function KenneloScan() {
    const t = useTranslations();

    return (
        <section data-slot="kennelo-scan" className="flex flex-col gap-8">
            <div className="flex flex-col gap-2 text-center">
                <h2 className="text-4xl font-bold tracking-tight">
                    {t("features.home.scan.title")}
                </h2>
                <p className="mx-auto max-w-xl text-sm text-muted-foreground">
                    {t("features.home.scan.subtitle")}
                </p>
            </div>
            <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                {FEATURES.map(({ key, Icon }) => (
                    <div key={key} className="flex flex-col items-center gap-3 text-center">
                        <span className="flex size-14 items-center justify-center rounded-2xl bg-secondary/15">
                            <Icon className="size-7 text-primary" />
                        </span>
                        <h3 className="font-semibold">{t(`features.home.scan.${key}.title`)}</h3>
                        <p className="max-w-xs text-sm text-muted-foreground">
                            {t(`features.home.scan.${key}.description`)}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}
