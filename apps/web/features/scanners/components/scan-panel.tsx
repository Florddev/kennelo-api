"use client";

import { Bluetooth, Loader2 } from "lucide-react";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";

import type { FoundDevice } from "../hooks/use-scanner-ble";

export function ScanPanel({
    scanning,
    devices,
    onScan,
    onConnect,
}: {
    scanning: boolean;
    devices: FoundDevice[];
    onScan: () => void;
    onConnect: (deviceId: string) => void;
}) {
    const t = useTranslations("features.scanners.add");

    return (
        <section className="grid gap-3">
            <Button onClick={onScan} disabled={scanning} className="rounded-4xl">
                {scanning ? (
                    <Loader2 className="me-2 size-4 animate-spin" />
                ) : (
                    <Bluetooth className="me-2 size-4" />
                )}
                {scanning ? t("scanning") : t("scanBtn")}
            </Button>

            {scanning && devices.length === 0 && (
                <p className="py-2 text-center text-sm text-muted-foreground">{t("noDevices")}</p>
            )}

            {devices.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {devices.map((d) => (
                        <li key={d.deviceId}>
                            <button
                                onClick={() => onConnect(d.deviceId)}
                                disabled={scanning}
                                className="w-full flex items-center justify-between rounded-2xl border px-4 py-3 transition-colors hover:bg-muted/50 active:bg-muted disabled:opacity-50"
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
