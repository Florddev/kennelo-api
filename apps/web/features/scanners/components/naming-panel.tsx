"use client";

import { Loader2 } from "lucide-react";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";

import type { Phase } from "../lib/scanner-phases";

export function NamingPanel({
    phase,
    name,
    onNameChange,
    onSave,
    saveError,
}: {
    phase: Phase;
    name: string;
    onNameChange: (name: string) => void;
    onSave: () => void;
    saveError: string | null;
}) {
    const t = useTranslations("features.scanners.add");

    return (
        <section className="grid gap-4">
            {phase === "connecting" && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    {t("identifying")}
                </div>
            )}

            {(phase === "naming" || phase === "saving") && (
                <>
                    <div className="grid gap-1.5">
                        <label className="text-sm font-medium">{t("nameLabel")}</label>
                        <Input
                            value={name}
                            onChange={(e) => onNameChange(e.target.value)}
                            placeholder={t("namePlaceholder")}
                            disabled={phase === "saving"}
                        />
                    </div>

                    {saveError && <p className="text-sm text-destructive">{saveError}</p>}

                    <Button onClick={onSave} disabled={phase === "saving"} className="rounded-4xl">
                        {phase === "saving" && <Loader2 className="me-2 size-4 animate-spin" />}
                        {phase === "saving" ? t("saving") : t("saveBtn")}
                    </Button>
                </>
            )}
        </section>
    );
}
