"use client";

import { useTranslations } from "next-intl";
import { Phone } from "@solar-icons/react";

import type { ScanOwnerModel } from "@workspace/modules/scanners";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";

export function ScannedPetOwnerCard({ owner }: { owner: ScanOwnerModel }) {
    const t = useTranslations("features.hosting-scan.detail");

    return (
        <div className="flex flex-col gap-3">
            <h2 className="text-xl font-semibold">{t("owner")}</h2>

            <div className="flex items-center gap-3 rounded-4xl border bg-card p-4">
                <Avatar className="size-12">
                    <AvatarImage src={owner.avatarUrl ?? undefined} alt={owner.getFullName()} />
                    <AvatarFallback>{owner.getInitials()}</AvatarFallback>
                </Avatar>

                <div className="flex min-w-0 flex-1 flex-col">
                    <span className="truncate text-sm font-medium">{owner.getFullName()}</span>
                    {owner.phone && (
                        <a
                            href={`tel:${owner.phone}`}
                            className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary"
                        >
                            <Phone className="size-3.5" />
                            {owner.phone}
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
