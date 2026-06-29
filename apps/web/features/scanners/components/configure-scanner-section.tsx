"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { ArrowLeft } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import type { ScannerModel } from "@workspace/modules/scanners";

import type { FoundDevice, BleErrorCode, ScannerMessage } from "../hooks/use-scanner-ble";
import { BleConnectionPanel } from "./ble-connection-panel";
import { WifiScanner } from "./wifi-scanner";
import { WifiSavedList } from "./wifi-saved-list";
import { WifiAddForm } from "./wifi-add-form";

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
        <div data-slot="configure-scanner-section" className="flex flex-col gap-6">
            <div className="flex items-center gap-3">
                <Button variant="flat" size="icon-sm" onClick={onBack}>
                    <ArrowLeft className="size-4" />
                </Button>
                <div className="min-w-0">
                    <h2 className="truncate text-lg font-semibold">{scanner.displayName}</h2>
                    <p className="text-sm text-muted-foreground">{t("title")}</p>
                </div>

                {codeMatches && (
                    <div className="ms-auto flex shrink-0 items-center gap-1.5">
                        <div className="size-2 rounded-full bg-green-500" />
                        <span className="text-sm text-green-600 dark:text-green-400">
                            {tCard("connected")}
                        </span>
                        <Button
                            variant="ghost"
                            size="xs"
                            onClick={onDisconnect}
                            className="ms-1 text-muted-foreground"
                        >
                            {t("disconnectBtn")}
                        </Button>
                    </div>
                )}
            </div>

            {!codeMatches && (
                <BleConnectionPanel
                    scanning={scanning}
                    connecting={connecting}
                    devices={devices}
                    error={error}
                    codeMismatch={codeMismatch}
                    showBleModeHint={showBleModeHint}
                    onScan={onScan}
                    onConnect={onConnect}
                />
            )}

            {codeMatches && (
                <>
                    {selectedSsid !== null ? (
                        <WifiAddForm
                            send={send}
                            subscribe={subscribe}
                            ssid={selectedSsid}
                            onSaved={() => setSelectedSsid(null)}
                            onCancel={() => setSelectedSsid(null)}
                        />
                    ) : (
                        <div className="flex flex-col gap-6">
                            <WifiScanner
                                send={send}
                                subscribe={subscribe}
                                onSelect={setSelectedSsid}
                            />
                            <Separator />
                            <WifiSavedList send={send} subscribe={subscribe} />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
