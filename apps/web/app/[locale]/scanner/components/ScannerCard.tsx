"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Settings2, Trash2 } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";
import type { ScannerModel } from "@workspace/modules/scanners";

export function ScannerCard({
    scanner,
    isConnected,
    isNearby,
    onConfigure,
    onDelete,
}: {
    scanner: ScannerModel;
    isConnected: boolean;
    isNearby: boolean;
    onConfigure: () => void;
    onDelete: () => Promise<void>;
}) {
    const t = useTranslations("features.scanners");
    const [deleting, setDeleting] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);

    const handleDelete = async () => {
        if (!confirmDelete) {
            setConfirmDelete(true);
            return;
        }
        setDeleting(true);
        try {
            await onDelete();
        } finally {
            setDeleting(false);
            setConfirmDelete(false);
        }
    };

    return (
        <div
            data-slot="scanner-card"
            className="flex items-center justify-between p-4 border rounded-2xl"
        >
            <div className="flex flex-col gap-0.5 min-w-0">
                <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-sm truncate">{scanner.displayName}</span>
                    {isConnected && (
                        <span className="shrink-0 text-xs text-green-600 bg-green-100 dark:bg-green-950 dark:text-green-400 px-2 py-0.5 rounded-full">
                            {t("card.connected")}
                        </span>
                    )}
                    {!isConnected && isNearby && (
                        <span className="shrink-0 text-xs text-blue-600 bg-blue-100 dark:bg-blue-950 dark:text-blue-400 px-2 py-0.5 rounded-full">
                            {t("card.available")}
                        </span>
                    )}
                </div>
                <span className="text-xs text-muted-foreground font-mono">
                    {t("card.code")} {scanner.code}
                </span>
            </div>

            <div className="flex items-center gap-2 shrink-0 ms-3">
                <button
                    onClick={onConfigure}
                    className="flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-4xl bg-primary text-primary-foreground"
                >
                    <Settings2 className="size-3.5" />
                    {t("card.configure")}
                </button>

                {confirmDelete ? (
                    <div className="flex items-center gap-1">
                        <button
                            onClick={handleDelete}
                            disabled={deleting}
                            className="px-2 py-1 text-xs rounded-4xl bg-destructive text-destructive-foreground disabled:opacity-50"
                        >
                            {t("deleteConfirm.confirm")}
                        </button>
                        <button
                            onClick={() => setConfirmDelete(false)}
                            className="px-2 py-1 text-xs rounded-4xl border text-muted-foreground"
                        >
                            {t("deleteConfirm.cancel")}
                        </button>
                    </div>
                ) : (
                    <button
                        onClick={handleDelete}
                        className={cn(
                            "p-1.5 rounded-xl text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors",
                        )}
                        aria-label={t("card.delete")}
                    >
                        <Trash2 className="size-4" />
                    </button>
                )}
            </div>
        </div>
    );
}
