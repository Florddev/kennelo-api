"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { ScanLine, Settings2, Trash2 } from "lucide-react";

import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { Card, CardContent } from "@workspace/ui/components/card";
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
        <Card data-slot="scanner-card" className="rounded-2xl">
            <CardContent className="flex items-center gap-3 px-4 py-3">
                <div className="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-muted">
                    <ScanLine className="size-5 text-muted-foreground" />
                </div>

                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <p className="text-sm font-medium truncate">{scanner.displayName}</p>
                        {isConnected && (
                            <Badge
                                variant="secondary"
                                size="sm"
                                className="bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400"
                            >
                                {t("card.connected")}
                            </Badge>
                        )}
                        {!isConnected && isNearby && (
                            <Badge variant="outline" size="sm">
                                {t("card.available")}
                            </Badge>
                        )}
                    </div>
                    <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                        {t("card.code")} {scanner.code}
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    {confirmDelete ? (
                        <>
                            <Button
                                size="xs"
                                variant="destructive"
                                onClick={handleDelete}
                                disabled={deleting}
                            >
                                {t("deleteConfirm.confirm")}
                            </Button>
                            <Button
                                size="xs"
                                variant="ghost"
                                onClick={() => setConfirmDelete(false)}
                            >
                                {t("deleteConfirm.cancel")}
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                onClick={onConfigure}
                                aria-label={t("card.configure")}
                            >
                                <Settings2 className="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                onClick={handleDelete}
                                aria-label={t("card.delete")}
                                className="text-destructive hover:text-destructive"
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
