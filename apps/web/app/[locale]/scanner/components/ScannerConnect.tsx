"use client";

import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";

export function ScannerConnect({
    connected,
    connecting,
    error,
    onConnect,
    onDisconnect,
}: {
    connected: boolean;
    connecting: boolean;
    error: string | null;
    onConnect: () => void;
    onDisconnect: () => void;
}) {
    const t = useTranslations("features.scanner");

    const statusLabel = connected
        ? t("connect.connected")
        : connecting
          ? t("connect.connecting")
          : t("connect.disconnected");

    const btnLabel = connected
        ? t("connect.disconnectBtn")
        : connecting
          ? t("connect.connectingBtn")
          : t("connect.connectBtn");

    return (
        <div className="flex flex-col items-center gap-3 p-4 border rounded-lg">
            <div className={cn("w-3 h-3 rounded-full", connected ? "bg-green-500" : "bg-muted")} />
            <span className="text-sm text-muted-foreground">{statusLabel}</span>
            {error && <p className="text-sm text-destructive">{error}</p>}
            <button
                onClick={connected ? onDisconnect : onConnect}
                disabled={connecting}
                className="px-4 py-2 rounded-4xl bg-primary text-primary-foreground text-sm disabled:opacity-50"
            >
                {btnLabel}
            </button>
        </div>
    );
}
