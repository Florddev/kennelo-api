"use client";

import { useState, useCallback } from "react";
import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";

import { ScannerMessage } from "../hooks/useScannerBle";

export function WifiAddForm({
    send,
    subscribe,
    ssid: initialSsid = "",
    onSaved,
    onCancel,
}: {
    send: (cmd: object) => Promise<void>;
    subscribe: (handler: (msg: ScannerMessage) => void) => () => void;
    ssid?: string;
    onSaved: () => void;
    onCancel: () => void;
}) {
    const t = useTranslations("features.scanner.wifi");
    const [ssid, setSsid] = useState(initialSsid);
    const [password, setPassword] = useState("");
    const [priority, setPriority] = useState(5);
    const [showPassword, setShowPassword] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const save = useCallback(async () => {
        if (!ssid.trim()) return;
        setSaving(true);
        setError(null);

        const unsub = subscribe((msg) => {
            if (msg.t === "ok") {
                unsub();
                setSaving(false);
                onSaved();
            } else if (msg.t === "err") {
                unsub();
                setSaving(false);
                setError(msg.m ?? t("unknownError"));
            }
        });

        try {
            await send({ t: "save", ssid: ssid.trim(), pass: password, pri: priority });
        } catch (e) {
            unsub();
            setSaving(false);
            setError(e instanceof Error ? e.message : t("sendError"));
        }
    }, [send, subscribe, ssid, password, priority, onSaved, t]);

    return (
        <div data-slot="wifi-add-form" className="flex flex-col gap-4 p-4 border rounded-2xl">
            <h2 className="font-semibold">{t("addTitle")}</h2>

            <div className="flex flex-col gap-1.5">
                <label className="text-sm font-medium">{t("ssidLabel")}</label>
                <input
                    value={ssid}
                    onChange={(e) => setSsid(e.target.value)}
                    placeholder={t("ssidPlaceholder")}
                    className={cn(
                        "w-full px-3 py-2 text-sm border rounded-xl bg-background",
                        "focus:outline-none focus:ring-2 focus:ring-primary/50",
                    )}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <label className="text-sm font-medium">{t("passwordLabel")}</label>
                <div className="flex gap-2">
                    <input
                        type={showPassword ? "text" : "password"}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder={t("passwordPlaceholder")}
                        className={cn(
                            "flex-1 px-3 py-2 text-sm border rounded-xl bg-background",
                            "focus:outline-none focus:ring-2 focus:ring-primary/50",
                        )}
                    />
                    <button
                        type="button"
                        onClick={() => setShowPassword((v) => !v)}
                        className="px-3 text-sm text-muted-foreground border rounded-xl hover:bg-muted/50 transition-colors"
                    >
                        {showPassword ? t("hidePassword") : t("showPassword")}
                    </button>
                </div>
            </div>

            <div className="flex flex-col gap-1.5">
                <label className="text-sm font-medium">{t("priorityLabel")}</label>
                <select
                    value={priority}
                    onChange={(e) => setPriority(Number(e.target.value))}
                    className={cn(
                        "w-full px-3 py-2 text-sm border rounded-xl bg-background",
                        "focus:outline-none focus:ring-2 focus:ring-primary/50",
                    )}
                >
                    {[1, 2, 3, 4, 5].map((p) => (
                        <option key={p} value={p}>
                            {p}
                        </option>
                    ))}
                </select>
            </div>

            {error && <p className="text-sm text-destructive">{error}</p>}

            <div className="flex gap-2 pt-1">
                <button
                    type="button"
                    onClick={onCancel}
                    className="flex-1 py-2 border rounded-4xl text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                >
                    {t("cancel")}
                </button>
                <button
                    type="button"
                    onClick={save}
                    disabled={saving || !ssid.trim()}
                    className="flex-1 py-2 rounded-4xl bg-primary text-primary-foreground text-sm disabled:opacity-50"
                >
                    {saving ? t("saving") : t("save")}
                </button>
            </div>
        </div>
    );
}
