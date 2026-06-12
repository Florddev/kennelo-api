"use client";

import { useState, useCallback, useEffect } from "react";

import type { ScannerModel } from "@workspace/modules/scanners";

import { useScannerBle } from "@/features/scanners/hooks/use-scanner-ble";
import { useScanners } from "@/features/scanners/hooks/use-scanners";
import { BLE_DEVICE_PREFIX } from "@/features/scanners/lib/scanner-ble";
import { ScannerList } from "@/features/scanners/components/scanner-list";
import { AddScannerSection } from "@/features/scanners/components/add-scanner-section";
import { ConfigureScannerSection } from "@/features/scanners/components/configure-scanner-section";

type Mode = "list" | "adding" | "configuring";

export function ScannersSettingsPage() {
    const [mode, setMode] = useState<Mode>("list");
    const [targetScanner, setTargetScanner] = useState<ScannerModel | null>(null);

    const {
        connected,
        connecting,
        scanning,
        backgroundScanning,
        devices,
        nearbyDevices,
        bleEnabled,
        error,
        scannedCode,
        checkAndScan,
        startScan,
        connectTo,
        disconnect,
        send,
        subscribe,
    } = useScannerBle();

    const { scanners, invalidate } = useScanners();

    useEffect(() => {
        if (mode === "list") checkAndScan();
    }, [mode, checkAndScan]);

    const handleAdd = useCallback(() => {
        disconnect();
        setMode("adding");
    }, [disconnect]);

    const handleConfigure = useCallback(
        (scanner: ScannerModel) => {
            const match = nearbyDevices.find(
                (d) => d.name === `${BLE_DEVICE_PREFIX}-${scanner.code}`,
            );
            disconnect();
            setTargetScanner(scanner);
            setMode("configuring");
            if (match) connectTo(match.deviceId);
        },
        [disconnect, nearbyDevices, connectTo],
    );

    const handleBack = useCallback(() => {
        disconnect();
        setMode("list");
        setTargetScanner(null);
    }, [disconnect]);

    const handleAdded = useCallback(
        (scanner: ScannerModel) => {
            invalidate();
            setTargetScanner(scanner);
            setMode("list");
        },
        [invalidate],
    );

    return (
        <div data-slot="scanners-settings-page" className="flex flex-col gap-6">
            {mode === "list" && (
                <ScannerList
                    connectedCode={scannedCode}
                    nearbyDevices={nearbyDevices}
                    backgroundScanning={backgroundScanning}
                    bleEnabled={bleEnabled}
                    onAdd={handleAdd}
                    onConfigure={handleConfigure}
                />
            )}

            {mode === "adding" && (
                <AddScannerSection
                    scanning={scanning}
                    devices={devices}
                    connecting={connecting}
                    scannedCode={scannedCode}
                    error={error}
                    onScan={startScan}
                    onConnect={connectTo}
                    onDisconnect={disconnect}
                    onBack={handleBack}
                    onAdded={handleAdded}
                    existingCodes={scanners.map((s) => s.code)}
                />
            )}

            {mode === "configuring" && targetScanner && (
                <ConfigureScannerSection
                    scanner={targetScanner}
                    connected={connected}
                    connecting={connecting}
                    scanning={scanning}
                    devices={devices}
                    scannedCode={scannedCode}
                    error={error}
                    onScan={startScan}
                    onConnect={connectTo}
                    onDisconnect={disconnect}
                    onBack={handleBack}
                    send={send}
                    subscribe={subscribe}
                />
            )}
        </div>
    );
}
