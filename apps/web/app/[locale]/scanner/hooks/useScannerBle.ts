import { useState, useCallback } from "react";
import { scannerBle, ScannerMessage, FoundDevice } from "../lib/scannerBle";

export type { ScannerMessage, FoundDevice };
export type BleErrorCode = "BLUETOOTH_DISABLED" | "SCAN_ERROR" | "CONNECT_FAILED";

export function useScannerBle() {
    const [connected, setConnected] = useState(false);
    const [connecting, setConnecting] = useState(false);
    const [scanning, setScanning] = useState(false);
    const [devices, setDevices] = useState<FoundDevice[]>([]);
    const [error, setError] = useState<BleErrorCode | null>(null);

    const startScan = useCallback(async () => {
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
        setError(null);
        try {
            await scannerBle.stopScan();
            await scannerBle.connectTo(deviceId);
            setConnected(true);
            setDevices([]);
        } catch {
            setError("CONNECT_FAILED");
        } finally {
            setConnecting(false);
            setScanning(false);
        }
    }, []);

    const disconnect = useCallback(async () => {
        await scannerBle.disconnect();
        setConnected(false);
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
        devices,
        error,
        startScan,
        connectTo,
        disconnect,
        send,
        subscribe,
    };
}
