"use client";

import { useTranslations } from "next-intl";
import { Plus, ScanLine, Bluetooth, BluetoothOff, Loader2 } from "lucide-react";

import type { ScannerModel } from "@workspace/modules/scanners";

import { useScanners } from "@/features/scanners/hooks/use-scanners";

import type { FoundDevice } from "../hooks/useScannerBle";
import { BLE_DEVICE_PREFIX } from "../lib/scannerBle";
import { ScannerCard } from "./ScannerCard";

export function ScannerListSection({
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
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-bold">{t("title")}</h1>
                <button
                    onClick={onAdd}
                    className="flex items-center gap-1.5 px-4 py-2 text-sm rounded-4xl bg-primary text-primary-foreground"
                >
                    <Plus className="size-4" />
                    {t("addBtn")}
                </button>
            </div>

            {bleEnabled === false && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
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
                <div className="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                    <Bluetooth className="size-3.5" />
                    {t("list.nearby", { count: nearbyDevices.length })}
                </div>
            )}

            {isLoading && (
                <div className="flex flex-col gap-2">
                    {[1, 2].map((i) => (
                        <div key={i} className="h-16 rounded-2xl bg-muted animate-pulse" />
                    ))}
                </div>
            )}

            {!isLoading && scanners.length === 0 && (
                <div className="flex flex-col items-center gap-3 py-12 text-center">
                    <ScanLine className="size-10 text-muted-foreground/50" />
                    <p className="text-sm font-medium">{t("noScanners")}</p>
                    <p className="text-xs text-muted-foreground">{t("noScannersHint")}</p>
                </div>
            )}

            {!isLoading && scanners.length > 0 && (
                <div className="flex flex-col gap-2">
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
        </div>
    );
}
