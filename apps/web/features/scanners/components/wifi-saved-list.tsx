"use client";

import { useState, useRef, useCallback } from "react";
import { useTranslations } from "next-intl";
import { Wifi, Trash2 } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";

import type { ScannerMessage } from "../hooks/use-scanner-ble";

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
            const unsub = subscribe((msg) => {
                if (msg.t === "ok" || msg.t === "err") {
                    unsub();
                    setDeleting(null);
                    if (msg.t === "ok") {
                        foundRef.current = foundRef.current.filter((n) => n.ssid !== ssid);
                        setNetworks([...foundRef.current]);
                    }
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
        <div data-slot="wifi-saved-list" className="flex flex-col gap-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="font-semibold">{t("saved")}</h3>
                    {networks.length === 0 && !loading && (
                        <p className="mt-0.5 text-sm text-muted-foreground">{t("noSaved")}</p>
                    )}
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    onClick={load}
                    disabled={loading}
                    className="shrink-0 rounded-4xl"
                >
                    {loading ? t("loading") : t("refresh")}
                </Button>
            </div>

            {networks.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {networks.map((net) => (
                        <li key={net.ssid}>
                            <Card className="rounded-2xl">
                                <CardContent className="flex items-center gap-3 px-4 py-3">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-xl bg-muted">
                                        <Wifi className="size-4 text-muted-foreground" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium truncate">{net.ssid}</p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {t("priority")} {net.priority}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        onClick={() => deleteNetwork(net.ssid)}
                                        disabled={deleting === net.ssid}
                                        aria-label={t("delete")}
                                        className="shrink-0 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </CardContent>
                            </Card>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
