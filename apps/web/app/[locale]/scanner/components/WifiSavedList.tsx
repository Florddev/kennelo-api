"use client";

import { useState, useRef, useCallback } from "react";
import { useTranslations } from "next-intl";

import { ScannerMessage } from "../hooks/useScannerBle";

type SavedNetwork = {
    ssid: string;
    priority: number;
};

export function WifiSavedList({
    send,
    subscribe,
}: {
    send: (cmd: object) => Promise<void>;
    subscribe: (handler: (msg: ScannerMessage) => void) => () => void;
}) {
    const t = useTranslations("features.scanner.wifi");
    const [loading, setLoading] = useState(false);
    const [networks, setNetworks] = useState<SavedNetwork[]>([]);
    const [deleting, setDeleting] = useState<string | null>(null);
    const foundRef = useRef<SavedNetwork[]>([]);

    const load = useCallback(async () => {
        setLoading(true);
        foundRef.current = [];
        setNetworks([]);

        const unsub = subscribe((msg) => {
            if (msg.t === "saved" && msg.ssid) {
                foundRef.current.push({ ssid: msg.ssid, priority: msg.pri ?? 5 });
                setNetworks([...foundRef.current]);
            } else if (msg.t === "list_done" || msg.t === "err") {
                setLoading(false);
                unsub();
            }
        });

        try {
            await send({ t: "list" });
        } catch {
            setLoading(false);
            unsub();
        }
    }, [send, subscribe]);

    const deleteNetwork = useCallback(
        async (ssid: string) => {
            setDeleting(ssid);
            const keepNetwork = (n: SavedNetwork) => n.ssid !== ssid;

            const unsub = subscribe((msg) => {
                if (msg.t === "ok" || msg.t === "err") {
                    unsub();
                    setDeleting(null);
                    if (msg.t === "ok") setNetworks((prev) => prev.filter(keepNetwork));
                }
            });

            try {
                await send({ t: "del", ssid });
            } catch {
                setDeleting(null);
                unsub();
            }
        },
        [send, subscribe],
    );

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
                <h2 className="font-semibold">{t("saved")}</h2>
                <button
                    onClick={load}
                    disabled={loading}
                    className="px-3 py-1 text-sm rounded-4xl bg-primary text-primary-foreground disabled:opacity-50"
                >
                    {loading ? t("loading") : t("refresh")}
                </button>
            </div>

            {networks.length === 0 && !loading && (
                <p className="text-sm text-muted-foreground text-center py-6">{t("noSaved")}</p>
            )}

            <ul className="flex flex-col gap-2">
                {networks.map((net) => (
                    <li
                        key={net.ssid}
                        className="flex items-center justify-between p-3 border rounded-2xl"
                    >
                        <div>
                            <span className="font-medium text-sm">{net.ssid}</span>
                            <span className="ms-2 text-xs text-muted-foreground">
                                {t("priority")} {net.priority}
                            </span>
                        </div>
                        <button
                            onClick={() => deleteNetwork(net.ssid)}
                            disabled={deleting === net.ssid}
                            className="text-sm text-destructive disabled:opacity-40"
                        >
                            {deleting === net.ssid ? "..." : t("delete")}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
