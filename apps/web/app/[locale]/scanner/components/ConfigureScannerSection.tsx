"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Bluetooth, BluetoothOff, ChevronLeft, Info, Loader2 } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import type { ScannerModel } from "@workspace/modules/scanners";

import type { FoundDevice, BleErrorCode, ScannerMessage } from "../hooks/useScannerBle";
import { WifiScanner } from "./WifiScanner";
import { WifiSavedList } from "./WifiSavedList";
import { WifiAddForm } from "./WifiAddForm";

function ScanConnectPanel({
    error,
    showBleModeHint,
    codeMismatch,
    connecting,
    scanning,
    devices,
    onScan,
    onConnect,
}: {
    error: BleErrorCode | null;
    showBleModeHint: boolean;
    codeMismatch: boolean;
    connecting: boolean;
    scanning: boolean;
    devices: FoundDevice[];
    onScan: () => void;
    onConnect: (deviceId: string) => void;
}) {
    const t = useTranslations("features.scanners.configure");
    const showHint = !connecting && !scanning && !codeMismatch && !error;

    return (
        <div className="flex flex-col gap-3 p-4 border rounded-2xl">
            {error && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
                    <p className="text-sm text-destructive">{t(`errors.${error}`)}</p>
                </div>
            )}

            {showBleModeHint && (
                <div className="flex items-start gap-2 p-3 bg-muted rounded-xl">
                    <Info className="size-4 text-muted-foreground shrink-0 mt-0.5" />
                    <p className="text-sm text-muted-foreground">{t("bleModeHint")}</p>
                </div>
            )}

            {codeMismatch && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
                    <p className="text-sm text-destructive">{t("wrongScanner")}</p>
                </div>
            )}

            {showHint && <p className="text-sm text-muted-foreground">{t("hint")}</p>}

            {connecting && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    {t("connecting")}
                </div>
            )}

            {!connecting && (
                <button
                    onClick={onScan}
                    disabled={scanning}
                    className="flex items-center justify-center gap-2 px-4 py-2 rounded-4xl bg-primary text-primary-foreground text-sm disabled:opacity-50"
                >
                    {scanning ? (
                        <Loader2 className="size-4 animate-spin" />
                    ) : (
                        <Bluetooth className="size-4" />
                    )}
                    {scanning ? t("scanning") : t("scanBtn")}
                </button>
            )}

            {scanning && devices.length === 0 && (
                <p className="text-sm text-muted-foreground text-center py-2">{t("scanning")}</p>
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
                                <span className="text-xs text-muted-foreground">{d.rssi} dBm</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function ConfigureScannerSection({
    scanner,
    connected,
    connecting,
    scanning,
    devices,
    scannedCode,
    error,
    onScan,
    onConnect,
    onDisconnect,
    onBack,
    send,
    subscribe,
}: {
    scanner: ScannerModel;
    connected: boolean;
    connecting: boolean;
    scanning: boolean;
    devices: FoundDevice[];
    scannedCode: string | null;
    error: BleErrorCode | null;
    onScan: () => void;
    onConnect: (deviceId: string) => void;
    onDisconnect: () => void;
    onBack: () => void;
    send: (cmd: object) => Promise<void>;
    subscribe: (handler: (msg: ScannerMessage) => void) => () => void;
}) {
    const t = useTranslations("features.scanners.configure");
    const tCard = useTranslations("features.scanners.card");
    const [selectedSsid, setSelectedSsid] = useState<string | null>(null);

    const codeMatches = connected && scannedCode === scanner.code;
    const codeMismatch = connected && scannedCode !== null && scannedCode !== scanner.code;
    const showBleModeHint = error === "CONNECT_FAILED" || error === "IDENTIFY_FAILED";

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
                <button
                    onClick={onBack}
                    className="p-1.5 rounded-xl text-muted-foreground hover:bg-muted/50 transition-colors"
                >
                    <ChevronLeft className="size-5" />
                </button>
                <div className="min-w-0">
                    <h2 className="text-lg font-semibold truncate">{scanner.displayName}</h2>
                    <p className="text-xs text-muted-foreground">{t("title")}</p>
                </div>
            </div>

            {!codeMatches && (
                <ScanConnectPanel
                    error={error}
                    showBleModeHint={showBleModeHint}
                    codeMismatch={codeMismatch}
                    connecting={connecting}
                    scanning={scanning}
                    devices={devices}
                    onScan={onScan}
                    onConnect={onConnect}
                />
            )}

            {codeMatches && (
                <>
                    <div className="flex items-center justify-between px-1">
                        <div
                            className={cn(
                                "flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400",
                            )}
                        >
                            <div className="size-2 rounded-full bg-green-500" />
                            {tCard("connected")}
                        </div>
                        <button
                            onClick={onDisconnect}
                            className="text-xs text-muted-foreground hover:text-foreground transition-colors"
                        >
                            {t("disconnectBtn")}
                        </button>
                    </div>

                    {selectedSsid !== null ? (
                        <WifiAddForm
                            send={send}
                            subscribe={subscribe}
                            ssid={selectedSsid}
                            onSaved={() => setSelectedSsid(null)}
                            onCancel={() => setSelectedSsid(null)}
                        />
                    ) : (
                        <>
                            <WifiScanner
                                send={send}
                                subscribe={subscribe}
                                onSelect={setSelectedSsid}
                            />
                            <WifiSavedList send={send} subscribe={subscribe} />
                        </>
                    )}
                </>
            )}
        </div>
    );
}
