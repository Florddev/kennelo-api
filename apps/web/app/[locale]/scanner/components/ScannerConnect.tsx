"use client";

import { useTranslations } from "next-intl";
import { Bluetooth, BluetoothOff, Loader2 } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";

import { type FoundDevice, type BleErrorCode } from "../hooks/useScannerBle";

export function ScannerConnect({
    connected,
    connecting,
    scanning,
    devices,
    error,
    onScan,
    onConnect,
    onDisconnect,
}: {
    connected: boolean;
    connecting: boolean;
    scanning: boolean;
    devices: FoundDevice[];
    error: BleErrorCode | null;
    onScan: () => void;
    onConnect: (deviceId: string) => void;
    onDisconnect: () => void;
}) {
    const t = useTranslations("features.scanner.connect");

    let statusLabel = t("disconnected");
    if (connected) statusLabel = t("connected");
    else if (connecting) statusLabel = t("connecting");

    return (
        <div className="flex flex-col gap-3 p-4 border rounded-2xl">
            <div className="flex items-center gap-2">
                <div
                    className={cn(
                        "size-2.5 rounded-full",
                        connected ? "bg-green-500" : "bg-muted-foreground/30",
                    )}
                />
                <span className="text-sm text-muted-foreground">{statusLabel}</span>
            </div>

            {error && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
                    <p className="text-sm text-destructive">{t(`errors.${error}`)}</p>
                </div>
            )}

            {!connected && (
                <>
                    <button
                        onClick={onScan}
                        disabled={scanning || connecting}
                        className="flex items-center justify-center gap-2 px-4 py-2 rounded-4xl bg-primary text-primary-foreground text-sm disabled:opacity-50"
                    >
                        {scanning ? (
                            <Loader2 className="size-4 animate-spin" />
                        ) : (
                            <Bluetooth className="size-4" />
                        )}
                        {scanning ? t("scanning") : t("scanBtn")}
                    </button>

                    {scanning && devices.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-2">
                            {t("noDevices")}
                        </p>
                    )}

                    {devices.length > 0 && (
                        <ul className="flex flex-col gap-2">
                            {devices.map((d) => (
                                <li key={d.deviceId}>
                                    <button
                                        onClick={() => onConnect(d.deviceId)}
                                        disabled={connecting}
                                        className="w-full flex items-center justify-between p-3 border rounded-2xl hover:bg-muted/50 active:bg-muted disabled:opacity-50 transition-colors"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Bluetooth className="size-4 text-primary" />
                                            <span className="text-sm font-medium">{d.name}</span>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {d.rssi} dBm
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </>
            )}

            {connected && (
                <button
                    onClick={onDisconnect}
                    className="px-4 py-2 rounded-4xl border text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                >
                    {t("disconnectBtn")}
                </button>
            )}
        </div>
    );
}
