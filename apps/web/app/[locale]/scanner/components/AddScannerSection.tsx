"use client";

import { useState, useEffect } from "react";
import { useTranslations } from "next-intl";
import { Bluetooth, BluetoothOff, ChevronLeft, Loader2 } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import { addScanner, ScannerModel } from "@workspace/modules/scanners";

import type { FoundDevice, BleErrorCode } from "../hooks/useScannerBle";

type Phase = "idle" | "scanning" | "connecting" | "naming" | "saving";

const NS = "features.scanners.add" as const;

function derivePhase(
    saving: boolean,
    scanning: boolean,
    connecting: boolean,
    scannedCode: string | null,
    alreadyAssociated: boolean,
): Phase {
    if (saving) return "saving";
    if (scanning) return "scanning";
    if (connecting) return "connecting";
    if (scannedCode && !alreadyAssociated) return "naming";
    return "idle";
}

function ScanPanel({
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
    const t = useTranslations(NS);

    return (
        <div className="flex flex-col gap-3 p-4 border rounded-2xl">
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

            {scanning && devices.length === 0 && (
                <p className="text-sm text-muted-foreground text-center py-2">{t("noDevices")}</p>
            )}

            {devices.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {devices.map((d) => (
                        <li key={d.deviceId}>
                            <button
                                onClick={() => onConnect(d.deviceId)}
                                disabled={scanning}
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

function FormPanel({
    phase,
    name,
    setName,
    saveError,
    onSave,
}: {
    phase: Phase;
    name: string;
    setName: (v: string) => void;
    saveError: string | null;
    onSave: () => void;
}) {
    const t = useTranslations(NS);
    const isSaving = phase === "saving";

    return (
        <div className="flex flex-col gap-4 p-4 border rounded-2xl">
            {phase === "connecting" && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    {t("identifying")}
                </div>
            )}

            {(phase === "naming" || isSaving) && (
                <>
                    <div className="flex flex-col gap-1.5">
                        <label className="text-sm font-medium">{t("nameLabel")}</label>
                        <input
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder={t("namePlaceholder")}
                            disabled={isSaving}
                            className={cn(
                                "w-full px-3 py-2 text-sm border rounded-xl bg-background",
                                "focus:outline-none focus:ring-2 focus:ring-primary/50",
                                "disabled:opacity-50",
                            )}
                        />
                    </div>

                    {saveError && <p className="text-sm text-destructive">{saveError}</p>}

                    <button
                        onClick={onSave}
                        disabled={isSaving}
                        className="flex items-center justify-center gap-2 px-4 py-2 rounded-4xl bg-primary text-primary-foreground text-sm disabled:opacity-50"
                    >
                        {isSaving && <Loader2 className="size-4 animate-spin" />}
                        {isSaving ? t("saving") : t("saveBtn")}
                    </button>
                </>
            )}
        </div>
    );
}

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
    const t = useTranslations(NS);
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
        <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
                <button
                    onClick={onBack}
                    className="p-1.5 rounded-xl text-muted-foreground hover:bg-muted/50 transition-colors"
                >
                    <ChevronLeft className="size-5" />
                </button>
                <h2 className="text-lg font-semibold">{t("title")}</h2>
            </div>

            {error && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
                    <p className="text-sm text-destructive">{t(`errors.${error}`)}</p>
                </div>
            )}

            {alreadyAssociated && (
                <div className="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-xl">
                    <BluetoothOff className="size-4 text-destructive shrink-0 mt-0.5" />
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
                <FormPanel
                    phase={phase}
                    name={name}
                    setName={setName}
                    saveError={saveError}
                    onSave={handleSave}
                />
            )}
        </div>
    );
}
