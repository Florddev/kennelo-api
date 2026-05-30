"use client";

import { useTranslations } from "next-intl";

export default function HostingNowPage() {
    const t = useTranslations();

    return (
        <div className="py-10">
            <h1 className="text-2xl font-semibold">{t("features.hosting-today.title")}</h1>
            <p className="text-muted-foreground mt-2">{t("features.hosting-today.description")}</p>
        </div>
    );
}
