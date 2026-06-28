"use client";

import { Bluetooth, BluetoothOff, Info, Loader2 } from "lucide-react";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import type { FoundDevice, BleErrorCode } from "../hooks/use-scanner-ble";

export function BleConnectionPanel({
    scanning,
    connecting,
    devices,
    error,
    codeMismatch,
    showBleModeHint,
    onScan,
    onConnect,
}: {
    scanning: boolean;
    connecting: boolean;
    devices: FoundDevice[];
    error: BleErrorCode | null;
    codeMismatch: boolean;
    showBleModeHint: boolean;
    onScan: () => void;
    onConnect: (deviceId: string) => void;
}) {
    const t = useTranslations("features.scanners.configure");

    return (
        <section className="grid gap-3">
            {error && (
                <div className="flex items-start gap-2.5 rounded-xl border border-destructive/20 bg-destructive/5 p-3.5">
                    <BluetoothOff className="mt-0.5 size-4 shrink-0 text-destructive" />
                    <p className="text-sm text-destructive">{t(`errors.${error}`)}</p>
                </div>
            )}

            {showBleModeHint && (
                <div className="flex items-start gap-2.5 rounded-xl bg-muted p-3.5">
                    <Info className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                    <p className="text-sm text-muted-foreground">{t("bleModeHint")}</p>
                </div>
            )}

            {codeMismatch && (
                <div className="flex items-start gap-2.5 rounded-xl border border-destructive/20 bg-destructive/5 p-3.5">
                    <BluetoothOff className="mt-0.5 size-4 shrink-0 text-destructive" />
                    <p className="text-sm text-destructive">{t("wrongScanner")}</p>
                </div>
            )}

            {!connecting && !scanning && !codeMismatch && !error && (
                <p className="text-sm text-muted-foreground">{t("hint")}</p>
            )}

            {connecting ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    {t("connecting")}
                </div>
            ) : (
                <Button onClick={onScan} disabled={scanning} className="rounded-4xl">
                    {scanning ? (
                        <Loader2 className="me-2 size-4 animate-spin" />
                    ) : (
                        <Bluetooth className="me-2 size-4" />
                    )}
                    {scanning ? t("scanning") : t("scanBtn")}
                </Button>
            )}

            {scanning && devices.length === 0 && (
                <p className="py-2 text-center text-sm text-muted-foreground">{t("scanning")}</p>
            )}

            {devices.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {devices.map((d) => (
                        <li key={d.deviceId}>
                            <button
                                onClick={() => onConnect(d.deviceId)}
                                disabled={connecting}
                                className={cn(
                                    "w-full flex items-center justify-between rounded-2xl border px-4 py-3",
                                    "transition-colors hover:bg-muted/50 active:bg-muted disabled:opacity-50",
                                )}
                            >
                                <div className="flex items-center gap-2.5">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                                        <Bluetooth className="size-4 text-primary" />
                                    </div>
                                    <span className="text-sm font-medium">{d.name}</span>
                                </div>
                                <span className="text-xs text-muted-foreground">{d.rssi} dBm</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
