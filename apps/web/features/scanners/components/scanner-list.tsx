"use client";

import { useTranslations } from "next-intl";
import { Plus, Bluetooth, BluetoothOff, Loader2, ScanLine } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import type { ScannerModel } from "@workspace/modules/scanners";

import { useScanners } from "../hooks/use-scanners";
import { BLE_DEVICE_PREFIX } from "../lib/scanner-ble";
import type { FoundDevice } from "../hooks/use-scanner-ble";
import { ScannerCard } from "./scanner-card";

export function ScannerList({
    connectedCode,
    nearbyDevices,
    backgroundScanning,
    bleEnabled,
    onAdd,
    onConfigure,
}: {
    connectedCode: string | null;
    nearbyDevices: FoundDevice[];
    backgroundScanning: boolean;
    bleEnabled: boolean | null;
    onAdd: () => void;
    onConfigure: (scanner: ScannerModel) => void;
}) {
    const t = useTranslations("features.scanners");
    const { scanners, isLoading, remove } = useScanners();

    return (
        <div data-slot="scanner-list" className="flex flex-col gap-6">
            <section className="grid gap-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="hidden text-lg font-semibold md:block">{t("title")}</h2>
                        <p className="mt-1 text-sm text-muted-foreground">{t("description")}</p>
                    </div>
                    <Button onClick={onAdd} className="shrink-0 rounded-4xl">
                        <Plus className="me-2 size-4" />
                        {t("addBtn")}
                    </Button>
                </div>

                {bleEnabled === false && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-destructive/20 bg-destructive/5 p-3.5">
                        <BluetoothOff className="mt-0.5 size-4 shrink-0 text-destructive" />
                        <p className="text-sm text-destructive">{t("list.bleDisabled")}</p>
                    </div>
                )}

                {bleEnabled === true && backgroundScanning && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 className="size-3.5 animate-spin" />
                        {t("list.scanning")}
                    </div>
                )}

                {bleEnabled === true && !backgroundScanning && nearbyDevices.length > 0 && (
                    <div className="flex items-center gap-2 text-sm text-primary">
                        <Bluetooth className="size-3.5" />
                        {t("list.nearby", { count: nearbyDevices.length })}
                    </div>
                )}

                {isLoading && (
                    <div className="flex flex-col gap-3">
                        <div className="h-[68px] animate-pulse rounded-2xl bg-muted" />
                        <div className="h-[68px] animate-pulse rounded-2xl bg-muted" />
                    </div>
                )}

                {!isLoading && scanners.length === 0 && (
                    <div className="rounded-2xl border border-dashed p-8 text-center">
                        <ScanLine className="mx-auto mb-3 size-8 text-muted-foreground/50" />
                        <p className="text-sm font-medium">{t("noScanners")}</p>
                        <p className="mt-1 text-xs text-muted-foreground">{t("noScannersHint")}</p>
                    </div>
                )}

                {!isLoading && scanners.length > 0 && (
                    <div className="flex flex-col gap-3">
                        {scanners.map((scanner) => (
                            <ScannerCard
                                key={scanner.id}
                                scanner={scanner}
                                isConnected={connectedCode === scanner.code}
                                isNearby={nearbyDevices.some(
                                    (d) => d.name === `${BLE_DEVICE_PREFIX}-${scanner.code}`,
                                )}
                                onConfigure={() => onConfigure(scanner)}
                                onDelete={() => remove(scanner)}
                            />
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}
