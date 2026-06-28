"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { ArrowLeft } from "lucide-react";

import { Button } from "@workspace/ui/components/button";

import { useNavigation } from "@/hooks/use-navigation";
import { UnknownChipPanel } from "./unknown-chip-panel";

export function ScannedPetUnknown({
    microchip,
    onAssigned,
}: {
    microchip: string;
    onAssigned: () => void;
}) {
    const t = useTranslations("features.hosting-scan.detail");
    const { routes } = useNavigation();

    return (
        <div className="mx-auto flex w-full max-w-lg flex-col gap-6 py-6">
            <Button variant="ghost" className="w-fit gap-1.5 ps-2" asChild>
                <Link href={routes.HostingScan()}>
                    <ArrowLeft className="size-4 rtl:rotate-180" />
                    {t("back")}
                </Link>
            </Button>

            <UnknownChipPanel microchipNumber={microchip} onAssigned={onAssigned} />
        </div>
    );
}
