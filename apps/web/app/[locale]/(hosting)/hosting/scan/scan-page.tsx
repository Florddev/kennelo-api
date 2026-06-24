"use client";

import { useTranslations } from "next-intl";
import { Magnifer } from "@solar-icons/react";

import PageLayout from "@/components/layouts/page-layout";
import { ScanHistoryList, ScanWaiting, useScannerScans } from "@/features/hosting-scan";

export function HostingScanPage() {
    const t = useTranslations();
    const { scans, isLoading } = useScannerScans();

    return (
        <PageLayout Icon={Magnifer} title={t("features.hosting-scan.title")}>
            <div className="mx-auto flex w-full max-w-lg flex-col gap-8">
                <ScanWaiting />
                <ScanHistoryList scans={scans} isLoading={isLoading} />
            </div>
        </PageLayout>
    );
}
