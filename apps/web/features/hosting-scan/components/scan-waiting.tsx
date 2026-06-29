"use client";

import { useTranslations } from "next-intl";
import { ScanLine } from "lucide-react";

export function ScanWaiting() {
    const t = useTranslations("features.hosting-scan.waiting");

    return (
        <div className="flex flex-col items-center justify-center gap-8 rounded-4xl border bg-card px-6 py-16 text-center">
            <div className="relative flex items-center justify-center">
                <span className="absolute size-40 rounded-full bg-primary/5 animate-ping [animation-duration:2.5s]" />
                <span className="absolute size-28 rounded-full bg-primary/10 animate-ping [animation-duration:2s]" />
                <span className="absolute size-20 rounded-full bg-primary/15" />
                <div className="relative flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg">
                    <ScanLine className="size-8" />
                </div>
            </div>

            <div className="flex flex-col items-center gap-2">
                <div className="inline-flex items-center gap-2 rounded-4xl bg-green-500/10 px-3 py-1">
                    <span className="size-2 rounded-full bg-green-500 animate-pulse" />
                    <span className="text-xs font-medium text-green-600 dark:text-green-400">
                        {t("listening")}
                    </span>
                </div>
                <h2 className="text-2xl font-bold">{t("title")}</h2>
                <p className="max-w-sm text-sm text-muted-foreground">{t("description")}</p>
            </div>
        </div>
    );
}
