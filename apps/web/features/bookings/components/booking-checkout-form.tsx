"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { Separator } from "@workspace/ui/components/separator";
import { createBooking } from "@workspace/modules/bookings";
import { computeNights, formatDateRange, toApiDate } from "@workspace/common";
import type { CapacityModel, EstablishmentModel } from "@workspace/modules/establishments";
import type { PetModel } from "@workspace/modules/pets";

import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { resolvePetsAvailability } from "@/features/host";
import { EstablishmentSummaryCard } from "@/features/establishments/components/establishment-summary-card";

import { computeBookingTotals } from "../lib/pricing";
import { BookingHeader } from "./booking-header";
import { BookingTripSection } from "./booking-trip-section";
import { BookingPetsSection } from "./booking-pets-section";
import { PriceBreakdown } from "./price-breakdown";
import { BookingMessageSection } from "./booking-message-section";
import { BookingFooter } from "./booking-footer";

type BookingCheckoutFormProps = {
    establishment: EstablishmentModel;
    capacities: CapacityModel[];
    pets: PetModel[];
    isLoadingPets: boolean;
    dateRange: { from: Date; to: Date };
};

export function BookingCheckoutForm({
    establishment,
    capacities,
    pets,
    isLoadingPets,
    dateRange,
}: BookingCheckoutFormProps) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const [selectedPetIds, setSelectedPetIds] = useState<string[]>([]);
    const [specialRequests, setSpecialRequests] = useState("");
    const { execute, isLoading: isSubmitting } = useAsyncState();

    const petsAvailability = resolvePetsAvailability(pets, capacities);
    const selectedPets = pets.filter((pet) => selectedPetIds.includes(pet.id));
    const nights = computeNights(dateRange.from, dateRange.to);
    const totals = computeBookingTotals(selectedPets, capacities, nights);
    const canSubmit = selectedPetIds.length > 0 && nights > 0;

    const togglePet = (petId: string) => {
        setSelectedPetIds((current) =>
            current.includes(petId)
                ? current.filter((value) => value !== petId)
                : [...current, petId],
        );
    };

    const handleSubmit = async () => {
        const result = await execute(
            () =>
                createBooking({
                    establishmentId: establishment.id,
                    checkInDate: toApiDate(dateRange.from),
                    checkOutDate: toApiDate(dateRange.to),
                    petIds: selectedPetIds,
                    specialRequests: specialRequests.trim() || undefined,
                }),
            { displayError: true },
        );
        if (result) {
            toast.success(t("features.bookings.checkout.successTitle"), {
                description: t("features.bookings.checkout.successDescription"),
            });
            router.push(routes.Explore());
        }
    };

    const locale = typeof navigator !== "undefined" ? navigator.language : "fr-FR";
    const datesLabel = formatDateRange(dateRange.from, dateRange.to, locale);
    const petsCountLabel = t("features.bookings.checkout.petsCount", {
        count: selectedPetIds.length,
    });

    return (
        <div className="relative flex flex-col bg-background pb-[140px] md:pb-20">
            <BookingHeader
                title={t("features.bookings.checkout.title")}
                onBack={() => router.back()}
            />

            <div className="flex flex-col gap-6 px-4 py-4">
                <EstablishmentSummaryCard establishment={establishment} />

                <BookingTripSection datesLabel={datesLabel} petsCountLabel={petsCountLabel} />

                <Separator />

                <BookingPetsSection
                    petsAvailability={petsAvailability}
                    selectedIds={selectedPetIds}
                    isLoading={isLoadingPets}
                    onToggle={togglePet}
                    managePetsHref={routes.MyPets()}
                />

                <Separator />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.priceSection")}
                    </h2>
                    <PriceBreakdown totals={totals} />
                </section>

                <Separator />

                <BookingMessageSection value={specialRequests} onChange={setSpecialRequests} />
            </div>

            <BookingFooter
                total={totals.total}
                canSubmit={canSubmit}
                isSubmitting={isSubmitting}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
