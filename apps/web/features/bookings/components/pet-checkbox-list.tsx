"use client";

import { useTranslations } from "next-intl";
import Link from "next/link";
import { PawPrint } from "lucide-react";
import { Checkbox } from "@workspace/ui/components/checkbox";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    EmptyDescription,
} from "@workspace/ui/components/empty";

import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import type { PetAvailability } from "@/features/host";

type PetCheckboxListProps = {
    petsAvailability: PetAvailability[];
    selectedIds: string[];
    onToggle: (petId: string) => void;
    managePetsHref: string;
};

export function PetCheckboxList({
    petsAvailability,
    selectedIds,
    onToggle,
    managePetsHref,
}: PetCheckboxListProps) {
    const t = useTranslations();

    if (petsAvailability.length === 0) {
        return (
            <Empty className="rounded-2xl border py-8">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <PawPrint />
                    </EmptyMedia>
                    <EmptyTitle>{t("features.bookings.checkout.noPetsTitle")}</EmptyTitle>
                    <EmptyDescription>
                        {t("features.bookings.checkout.noPets")}
                    </EmptyDescription>
                </EmptyHeader>
                <Button asChild variant="secondary" className="rounded-full">
                    <Link href={managePetsHref}>{t("features.bookings.checkout.goToPets")}</Link>
                </Button>
            </Empty>
        );
    }

    return (
        <ul className="flex flex-col gap-2">
            {petsAvailability.map((availability) => (
                <PetRow
                    key={availability.pet.id}
                    availability={availability}
                    isSelected={selectedIds.includes(availability.pet.id)}
                    onToggle={onToggle}
                    reasonLabel={resolveReasonLabel(availability.status, t)}
                />
            ))}
        </ul>
    );
}

type ReasonStatus = PetAvailability["status"];

function resolveReasonLabel(
    status: ReasonStatus,
    t: (key: string) => string,
): string | null {
    if (status === "type-not-accepted") {
        return t("features.bookings.checkout.petUnavailableType");
    }
    if (status === "capacity-full") {
        return t("features.bookings.checkout.petUnavailableCapacity");
    }
    return null;
}

function PetRow({
    availability,
    isSelected,
    onToggle,
    reasonLabel,
}: {
    availability: PetAvailability;
    isSelected: boolean;
    onToggle: (petId: string) => void;
    reasonLabel: string | null;
}) {
    const { pet, status } = availability;
    const isAvailable = status === "available";
    const isChecked = isAvailable && isSelected;

    return (
        <li>
            <label
                className={cn(
                    "flex items-center gap-3 rounded-2xl border p-3 transition-colors",
                    isAvailable ? "cursor-pointer" : "cursor-not-allowed opacity-60",
                    isChecked ? "border-foreground bg-muted/40" : "border-border",
                    isAvailable && !isChecked && "hover:bg-muted/30",
                )}
            >
                <Checkbox
                    checked={isChecked}
                    disabled={!isAvailable}
                    onCheckedChange={() => isAvailable && onToggle(pet.id)}
                />
                {pet.animalType && (
                    <PetTypeIllustration
                        code={pet.animalType.code}
                        name={pet.animalType.name}
                        className="size-8"
                    />
                )}
                <div className="flex flex-1 flex-col">
                    <span className="text-sm font-semibold">{pet.name}</span>
                    {pet.breed && (
                        <span className="text-xs text-muted-foreground">{pet.breed}</span>
                    )}
                    {reasonLabel && (
                        <span className="mt-1 text-xs text-amber-700">{reasonLabel}</span>
                    )}
                </div>
            </label>
        </li>
    );
}
