"use client";

import { useState } from "react";

import { useScannerBle } from "./hooks/useScannerBle";
import { ScannerConnect } from "./components/ScannerConnect";
import { WifiScanner } from "./components/WifiScanner";
import { WifiSavedList } from "./components/WifiSavedList";
import { WifiAddForm } from "./components/WifiAddForm";

export function ScannerConfigPage() {
    const { connected, connecting, error, connect, disconnect, send, subscribe } = useScannerBle();
    const [selectedSsid, setSelectedSsid] = useState<string | null>(null);

    return (
        <main className="max-w-md mx-auto p-4 flex flex-col gap-6">
            <ScannerConnect
                connected={connected}
                connecting={connecting}
                error={error}
                onConnect={connect}
                onDisconnect={disconnect}
            />

            {connected && (
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
        </main>
    );
}
