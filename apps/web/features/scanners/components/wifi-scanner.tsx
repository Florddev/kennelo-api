"use client";

import { useState, useRef, useCallback } from "react";
import { useTranslations } from "next-intl";
import { Wifi } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";

import type { ScannerMessage } from "../hooks/use-scanner-ble";
import { signalBars } from "../lib/wifi-signal";

type WifiNetwork = {
    ssid: string;
    rssi: number;
};

export function WifiScanner({
    send,
    subscribe,
    onSelect,
}: {
    send: (cmd: object) => Promise<void>;
    subscribe: (handler: (msg: ScannerMessage) => void) => () => void;
    onSelect: (ssid: string) => void;
}) {
    const t = useTranslations("features.scanner.wifi");
    const [scanning, setScanning] = useState(false);
    const [networks, setNetworks] = useState<WifiNetwork[]>([]);
    const foundRef = useRef<WifiNetwork[]>([]);

    const scan = useCallback(async () => {
        setScanning(true);
        foundRef.current = [];
        setNetworks([]);

        const unsub = subscribe((msg) => {
            if (msg.t === "net" && msg.ssid) {
                foundRef.current.push({ ssid: msg.ssid, rssi: msg.rssi ?? 0 });
                setNetworks([...foundRef.current]);
            } else if (msg.t === "scan_done" || msg.t === "err") {
                setScanning(false);
                unsub();
            }
        });

        try {
            await send({ t: "scan" });
        } catch {
            setScanning(false);
            unsub();
        }
    }, [send, subscribe]);

    return (
        <div data-slot="wifi-scanner" className="flex flex-col gap-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="font-semibold">{t("available")}</h3>
                    <p className="mt-0.5 text-sm text-muted-foreground">{t("noNetworks")}</p>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    onClick={scan}
                    disabled={scanning}
                    className="shrink-0 rounded-4xl"
                >
                    {scanning ? t("scanning") : t("scan")}
                </Button>
            </div>

            {networks.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {networks.map((net) => (
                        <li key={net.ssid}>
                            <Card
                                className="cursor-pointer rounded-2xl transition-colors hover:bg-muted/50 active:bg-muted"
                                onClick={() => onSelect(net.ssid)}
                            >
                                <CardContent className="flex items-center justify-between px-4 py-3">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 shrink-0 items-center justify-center rounded-xl bg-muted">
                                            <Wifi className="size-4 text-muted-foreground" />
                                        </div>
                                        <span className="text-sm font-medium">{net.ssid}</span>
                                    </div>
                                    <span className="font-mono text-xs text-muted-foreground">
                                        {signalBars(net.rssi)}
                                    </span>
                                </CardContent>
                            </Card>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
