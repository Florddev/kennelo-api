"use client";

import { useState, useEffect } from "react";
import { useTranslations } from "next-intl";
import { ArrowLeft, BluetoothOff } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { addScanner, type ScannerModel } from "@workspace/modules/scanners";

import type { FoundDevice, BleErrorCode } from "../hooks/use-scanner-ble";
import { derivePhase } from "../lib/scanner-phases";
import { ScanPanel } from "./scan-panel";
import { NamingPanel } from "./naming-panel";

export function AddScannerSection({
    scanning,
    devices,
    connecting,
    scannedCode,
    error,
    onScan,
    onConnect,
    onDisconnect,
    onBack,
    onAdded,
    existingCodes,
}: {
    scanning: boolean;
    devices: FoundDevice[];
    connecting: boolean;
    scannedCode: string | null;
    error: BleErrorCode | null;
    onScan: () => void;
    onConnect: (deviceId: string) => void;
    onDisconnect: () => void;
    onBack: () => void;
    onAdded: (scanner: ScannerModel) => void;
    existingCodes: string[];
}) {
    const t = useTranslations("features.scanners.add");
    const [name, setName] = useState("");
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const alreadyAssociated = !connecting && !!scannedCode && existingCodes.includes(scannedCode);
    const phase = derivePhase(saving, scanning, connecting, scannedCode, alreadyAssociated);
    const showScanPanel = phase === "idle" || phase === "scanning";

    useEffect(() => {
        if (alreadyAssociated) onDisconnect();
    }, [alreadyAssociated, onDisconnect]);

    const handleSave = async () => {
        if (!scannedCode) return;
        setSaving(true);
        setSaveError(null);
        try {
            const scanner = await addScanner({ code: scannedCode, name: name.trim() || undefined });
            onDisconnect();
            onAdded(scanner);
        } catch {
            setSaveError(t("errors.CONNECT_FAILED"));
            setSaving(false);
        }
    };

    return (
        <div data-slot="add-scanner-section" className="flex flex-col gap-4">
            <div className="flex items-center gap-3">
                <Button variant="flat" size="icon-sm" onClick={onBack}>
                    <ArrowLeft className="size-4" />
                </Button>
                <p className="text-sm text-muted-foreground">{t("description")}</p>
            </div>

            {error && (
                <div className="flex items-start gap-2.5 rounded-xl border border-destructive/20 bg-destructive/5 p-3.5">
                    <BluetoothOff className="mt-0.5 size-4 shrink-0 text-destructive" />
                    <p className="text-sm text-destructive">{t(`errors.${error}`)}</p>
                </div>
            )}

            {alreadyAssociated && (
                <div className="flex items-start gap-2.5 rounded-xl border border-destructive/20 bg-destructive/5 p-3.5">
                    <BluetoothOff className="mt-0.5 size-4 shrink-0 text-destructive" />
                    <p className="text-sm text-destructive">{t("alreadyAssociated")}</p>
                </div>
            )}

            {showScanPanel && (
                <ScanPanel
                    scanning={scanning}
                    devices={devices}
                    onScan={onScan}
                    onConnect={onConnect}
                />
            )}

            {!showScanPanel && (
                <NamingPanel
                    phase={phase}
                    name={name}
                    onNameChange={setName}
                    onSave={handleSave}
                    saveError={saveError}
                />
            )}
        </div>
    );
}
