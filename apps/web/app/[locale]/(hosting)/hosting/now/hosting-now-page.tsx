"use client";

import { useTranslations } from "next-intl";
import { SunFog } from "@solar-icons/react";

import PageLayout from "@/components/layouts/page-layout";

export default function HostingNowPage() {
    const t = useTranslations();

    return (
        <PageLayout Icon={SunFog} title={t("features.hosting-today.title")}>
            <p className="text-muted-foreground">{t("features.hosting-today.description")}</p>
        </PageLayout>
    );
}
