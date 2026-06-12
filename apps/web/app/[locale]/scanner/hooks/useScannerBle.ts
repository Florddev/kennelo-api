import { useState, useCallback } from "react";
import { scannerBle, ScannerMessage, FoundDevice } from "../lib/scannerBle";

export type { ScannerMessage, FoundDevice };
export type BleErrorCode =
    | "BLUETOOTH_DISABLED"
    | "SCAN_ERROR"
    | "CONNECT_FAILED"
    | "IDENTIFY_FAILED";

const BACKGROUND_SCAN_DURATION = 5000;

export function useScannerBle() {
    const [connected, setConnected] = useState(false);
    const [connecting, setConnecting] = useState(false);
    const [scanning, setScanning] = useState(false);
    const [backgroundScanning, setBackgroundScanning] = useState(false);
    const [devices, setDevices] = useState<FoundDevice[]>([]);
    const [nearbyDevices, setNearbyDevices] = useState<FoundDevice[]>([]);
    const [bleEnabled, setBleEnabled] = useState<boolean | null>(null);
    const [error, setError] = useState<BleErrorCode | null>(null);
    const [scannedCode, setScannedCode] = useState<string | null>(null);

    const checkAndScan = useCallback(async () => {
        if (scanning || connecting || connected) return;
        setBackgroundScanning(true);
        setNearbyDevices([]);
        const found: FoundDevice[] = [];
        try {
            await scannerBle.checkBluetooth();
            setBleEnabled(true);
            await scannerBle.scanDevices((device) => {
                if (found.some((d) => d.deviceId === device.deviceId)) return;
                found.push(device);
                setNearbyDevices([...found]);
            });
            await new Promise<void>((resolve) => setTimeout(resolve, BACKGROUND_SCAN_DURATION));
            await scannerBle.stopScan().catch(() => void 0);
        } catch (e) {
            if (e instanceof Error && e.message === "BLUETOOTH_DISABLED") {
                setBleEnabled(false);
            }
        } finally {
            setBackgroundScanning(false);
        }
    }, [scanning, connecting, connected]);

    const startScan = useCallback(async () => {
        await scannerBle.stopScan().catch(() => void 0);
        setBackgroundScanning(false);
        setScanning(true);
        setDevices([]);
        setError(null);
        const found: FoundDevice[] = [];
        try {
            await scannerBle.scanDevices((device) => {
                if (found.some((d) => d.deviceId === device.deviceId)) return;
                found.push(device);
                setDevices([...found]);
            });
            setTimeout(() => setScanning(false), 8000);
        } catch (e) {
            setError(
                e instanceof Error && e.message === "BLUETOOTH_DISABLED"
                    ? "BLUETOOTH_DISABLED"
                    : "SCAN_ERROR",
            );
            setScanning(false);
        }
    }, []);

    const connectTo = useCallback(async (deviceId: string) => {
        setConnecting(true);
        setBackgroundScanning(false);
        setError(null);
        setScannedCode(null);
        try {
            await scannerBle.stopScan();
            await scannerBle.connectTo(deviceId);
            setDevices([]);
            const code = await scannerBle.getInfo();
            setScannedCode(code);
            setConnected(true);
        } catch (e) {
            const isIdentifyError =
                e instanceof Error && (e.message === "INFO_FAILED" || e.message === "INFO_TIMEOUT");
            setError(isIdentifyError ? "IDENTIFY_FAILED" : "CONNECT_FAILED");
            await scannerBle.disconnect().catch(() => void 0);
        } finally {
            setConnecting(false);
            setScanning(false);
        }
    }, []);

    const disconnect = useCallback(async () => {
        await scannerBle.stopScan().catch(() => void 0);
        await scannerBle.disconnect();
        setConnected(false);
        setScannedCode(null);
        setBackgroundScanning(false);
    }, []);

    const send = useCallback((cmd: object) => scannerBle.send(cmd), []);
    const subscribe = useCallback(
        (handler: (msg: ScannerMessage) => void) => scannerBle.subscribe(handler),
        [],
    );

    return {
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
    };
}
