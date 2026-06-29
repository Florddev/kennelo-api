"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { toast } from "sonner";
import { Cpu, PawPrint } from "lucide-react";

import { assignPetMicrochip, type InCarePetModel } from "@workspace/modules/scanners";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Skeleton } from "@workspace/ui/components/skeleton";

import { useAsyncState } from "@/hooks/use-async-state";
import { useInCarePets } from "../hooks/use-in-care-pets";

const UNKNOWN_NS = "features.hosting-scan.unknown";

function InCarePetRow({
    pet,
    isAssigning,
    onAssign,
}: {
    pet: InCarePetModel;
    isAssigning: boolean;
    onAssign: (petId: string) => void;
}) {
    const t = useTranslations(UNKNOWN_NS);

    return (
        <div className="flex items-center gap-3 rounded-2xl border bg-card p-3">
            <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted">
                {pet.avatarUrl ? (
                    <Image
                        src={pet.avatarUrl}
                        alt={pet.name}
                        width={48}
                        height={48}
                        className="size-full object-cover"
                    />
                ) : (
                    <PawPrint className="size-5 text-muted-foreground" />
                )}
            </div>

            <div className="flex min-w-0 flex-1 flex-col">
                <span className="truncate text-sm font-medium">{pet.name}</span>
                {pet.ownerName && (
                    <span className="truncate text-xs text-muted-foreground">
                        {t("ownerLabel")}: {pet.ownerName}
                    </span>
                )}
                {pet.activityName && (
                    <span className="truncate text-xs text-muted-foreground">
                        {t("activityLabel")}: {pet.activityName}
                    </span>
                )}
            </div>

            {pet.hasMicrochip ? (
                <Badge variant="outline" className="shrink-0">
                    {t("alreadyHasChip")}
                </Badge>
            ) : (
                <Button
                    size="sm"
                    className="shrink-0"
                    disabled={isAssigning}
                    onClick={() => onAssign(pet.id)}
                >
                    {isAssigning ? t("assigning") : t("assign")}
                </Button>
            )}
        </div>
    );
}

function InCareList({
    pets,
    isLoading,
    assigningId,
    onAssign,
}: {
    pets: InCarePetModel[];
    isLoading: boolean;
    assigningId: string | null;
    onAssign: (petId: string) => void;
}) {
    const t = useTranslations(UNKNOWN_NS);

    if (isLoading) {
        return (
            <div className="flex flex-col gap-2">
                {[0, 1].map((index) => (
                    <Skeleton key={index} className="h-20 w-full rounded-2xl" />
                ))}
            </div>
        );
    }

    if (pets.length === 0) {
        return (
            <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed p-10 text-center">
                <PawPrint className="size-10 text-muted-foreground opacity-20" />
                <p className="text-sm text-muted-foreground">{t("inCareEmpty")}</p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            {pets.map((pet) => (
                <InCarePetRow
                    key={pet.id}
                    pet={pet}
                    isAssigning={assigningId === pet.id}
                    onAssign={onAssign}
                />
            ))}
        </div>
    );
}

export function UnknownChipPanel({
    microchipNumber,
    onAssigned,
}: {
    microchipNumber: string;
    onAssigned: () => void;
}) {
    const t = useTranslations(UNKNOWN_NS);
    const { pets, isLoading, refetch } = useInCarePets(true);
    const { execute } = useAsyncState();
    const [assigningId, setAssigningId] = useState<string | null>(null);

    const handleAssign = (petId: string) => {
        setAssigningId(petId);
        execute(() => assignPetMicrochip(petId, microchipNumber), {
            displayError: true,
            onSuccess: () => {
                toast.success(t("assignSuccess"));
                refetch();
                onAssigned();
            },
        });
    };

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-col items-center gap-4 rounded-4xl border bg-card px-6 py-10 text-center">
                <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                    <Cpu className="size-8 text-muted-foreground" />
                </div>
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-bold">{t("title")}</h1>
                    <p className="max-w-sm text-sm text-muted-foreground">{t("description")}</p>
                </div>
                <div className="flex flex-col items-center gap-1 rounded-2xl bg-muted px-4 py-2">
                    <span className="text-xs text-muted-foreground">{t("chipLabel")}</span>
                    <span className="font-mono text-sm font-medium">{microchipNumber}</span>
                </div>
            </div>

            <div className="flex flex-col gap-3">
                <h2 className="text-xl font-semibold">{t("inCareTitle")}</h2>
                <InCareList
                    pets={pets}
                    isLoading={isLoading}
                    assigningId={assigningId}
                    onAssign={handleAssign}
                />
            </div>
        </div>
    );
}
