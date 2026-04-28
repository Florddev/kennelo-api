"use client";

import { useTranslations } from "next-intl";
import { Skeleton } from "@workspace/ui/components/skeleton";

import type { PetAvailability } from "@/features/host";
import { PetCheckboxList } from "./pet-checkbox-list";

type BookingPetsSectionProps = {
    petsAvailability: PetAvailability[];
    selectedIds: string[];
    isLoading: boolean;
    onToggle: (petId: string) => void;
    managePetsHref: string;
};

export function BookingPetsSection({
    petsAvailability,
    selectedIds,
    isLoading,
    onToggle,
    managePetsHref,
}: BookingPetsSectionProps) {
    const t = useTranslations();

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.bookings.checkout.selectPets")}
            </h2>
            {isLoading ? (
                <div className="flex flex-col gap-2">
                    <Skeleton className="h-16 rounded-2xl" />
                    <Skeleton className="h-16 rounded-2xl" />
                </div>
            ) : (
                <PetCheckboxList
                    petsAvailability={petsAvailability}
                    selectedIds={selectedIds}
                    onToggle={onToggle}
                    managePetsHref={managePetsHref}
                />
            )}
        </section>
    );
}
