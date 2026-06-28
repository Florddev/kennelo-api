"use client";

import { useState, useCallback } from "react";
import { useTranslations } from "next-intl";
import { Eye, EyeOff } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";

import type { ScannerMessage } from "../hooks/use-scanner-ble";

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
    const [priority, setPriority] = useState("5");
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
            await send({ t: "save", ssid: ssid.trim(), pass: password, pri: Number(priority) });
        } catch (e) {
            unsub();
            setSaving(false);
            setError(e instanceof Error ? e.message : t("sendError"));
        }
    }, [send, subscribe, ssid, password, priority, onSaved, t]);

    return (
        <div data-slot="wifi-add-form" className="flex flex-col gap-6">
            <div>
                <h3 className="font-semibold">{t("addTitle")}</h3>
                <p className="mt-0.5 text-sm text-muted-foreground">
                    {ssid || t("ssidPlaceholder")}
                </p>
            </div>

            <div className="grid gap-4">
                <div className="grid gap-1.5">
                    <label className="text-sm font-medium">{t("ssidLabel")}</label>
                    <Input
                        value={ssid}
                        onChange={(e) => setSsid(e.target.value)}
                        placeholder={t("ssidPlaceholder")}
                        disabled={saving}
                    />
                </div>

                <div className="grid gap-1.5">
                    <label className="text-sm font-medium">{t("passwordLabel")}</label>
                    <div className="flex gap-2">
                        <Input
                            type={showPassword ? "text" : "password"}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder={t("passwordPlaceholder")}
                            disabled={saving}
                            className="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={() => setShowPassword((v) => !v)}
                            aria-label={showPassword ? t("hidePassword") : t("showPassword")}
                        >
                            {showPassword ? (
                                <EyeOff className="size-4" />
                            ) : (
                                <Eye className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <label className="text-sm font-medium">{t("priorityLabel")}</label>
                    <Select value={priority} onValueChange={setPriority} disabled={saving}>
                        <SelectTrigger className="rounded-4xl">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {["1", "2", "3", "4", "5"].map((p) => (
                                <SelectItem key={p} value={p}>
                                    {p}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {error && <p className="text-sm text-destructive">{error}</p>}

            <div className="flex gap-2">
                <Button
                    variant="outline"
                    onClick={onCancel}
                    disabled={saving}
                    className="flex-1 rounded-4xl"
                >
                    {t("cancel")}
                </Button>
                <Button
                    onClick={save}
                    disabled={saving || !ssid.trim()}
                    className="flex-1 rounded-4xl"
                >
                    {saving ? t("saving") : t("save")}
                </Button>
            </div>
        </div>
    );
}
