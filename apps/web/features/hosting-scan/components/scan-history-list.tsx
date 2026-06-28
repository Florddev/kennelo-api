"use client";

import { useTranslations } from "next-intl";
import Image from "next/image";
import { Cpu, History } from "lucide-react";

import type { ScannerScanModel } from "@workspace/modules/scanners";
import { Badge } from "@workspace/ui/components/badge";
import { Skeleton } from "@workspace/ui/components/skeleton";

const HISTORY_NS = "features.hosting-scan.history";

function ScanHistorySkeleton() {
    return (
        <div className="flex flex-col gap-2">
            {[0, 1, 2].map((index) => (
                <div key={index} className="flex items-center gap-3 rounded-2xl border bg-card p-3">
                    <Skeleton className="size-10 rounded-full shrink-0" />
                    <div className="flex flex-1 flex-col gap-1.5">
                        <Skeleton className="h-4 w-32" />
                        <Skeleton className="h-3 w-24" />
                    </div>
                    <Skeleton className="h-3 w-16" />
                </div>
            ))}
        </div>
    );
}

function ScanHistoryItem({ scan }: { scan: ScannerScanModel }) {
    const t = useTranslations(HISTORY_NS);

    return (
        <div className="flex items-center gap-3 rounded-2xl border bg-card p-3">
            <div className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted">
                {scan.pet?.avatarUrl ? (
                    <Image
                        src={scan.pet.avatarUrl}
                        alt={scan.pet.name}
                        width={40}
                        height={40}
                        className="size-full object-cover"
                    />
                ) : (
                    <Cpu className="size-5 text-muted-foreground" />
                )}
            </div>

            <div className="flex min-w-0 flex-1 flex-col">
                {scan.pet ? (
                    <span className="truncate text-sm font-medium">{scan.pet.name}</span>
                ) : (
                    <Badge variant="outline" className="w-fit">
                        {t("notFound")}
                    </Badge>
                )}
                <span className="truncate text-xs text-muted-foreground">
                    {scan.microchipNumber}
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    {t("via", { scanner: scan.scannerDisplayName })}
                </span>
            </div>

            <span className="shrink-0 text-xs text-muted-foreground">{scan.scannedAt}</span>
        </div>
    );
}

function ScanHistoryContent({
    scans,
    isLoading,
}: {
    scans: ScannerScanModel[];
    isLoading: boolean;
}) {
    const t = useTranslations(HISTORY_NS);

    if (isLoading) {
        return <ScanHistorySkeleton />;
    }

    if (scans.length === 0) {
        return (
            <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed p-10 text-center">
                <History className="size-10 text-muted-foreground opacity-20" />
                <p className="text-sm text-muted-foreground">{t("empty")}</p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            {scans.map((scan) => (
                <ScanHistoryItem key={scan.id} scan={scan} />
            ))}
        </div>
    );
}

export function ScanHistoryList({
    scans,
    isLoading,
}: {
    scans: ScannerScanModel[];
    isLoading: boolean;
}) {
    const t = useTranslations(HISTORY_NS);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-col">
                <h2 className="text-xl font-semibold">{t("title")}</h2>
                <p className="text-sm text-muted-foreground">{t("description")}</p>
            </div>

            <ScanHistoryContent scans={scans} isLoading={isLoading} />
        </div>
    );
}
