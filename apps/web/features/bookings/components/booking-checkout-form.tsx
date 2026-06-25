"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { Separator } from "@workspace/ui/components/separator";
import { createBooking } from "@workspace/modules/bookings";
import { computeNights, formatDateRange, toApiDate } from "@workspace/common";
import type {
    ActivityCycleSettingModel,
    ActivityModel,
    AvailabilityModel,
    PriceCalendar,
} from "@workspace/modules/activities";
import type { PetModel } from "@workspace/modules/pets";
import type { DateRange } from "react-day-picker";

import { getStripe } from "@/lib/stripe";
import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { acceptedAnimalTypeIds, resolvePetsAvailability } from "@/features/host";
import { ActivitySummaryCard } from "@/features/activities/components/activity-summary-card";
import { PaymentMethodPicker } from "@/features/payment-methods/components/payment-method-picker";

import { computeBookingTotals } from "../lib/pricing";

const stripePromise = getStripe();
import { BookingHeader } from "./booking-header";
import { BookingTripSection } from "./booking-trip-section";
import { BookingPetsSection } from "./booking-pets-section";
import { PriceBreakdown } from "./price-breakdown";
import { BookingMessageSection } from "./booking-message-section";
import { BookingFooter } from "./booking-footer";

type BookingCheckoutFormProps = {
    activity: ActivityModel;
    capacities: ActivityCycleSettingModel[];
    pets: PetModel[];
    isLoadingPets: boolean;
    initialDateRange: { from: Date; to: Date } | null;
    initialPetIds: string[];
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
};

export function BookingCheckoutForm({
    activity,
    capacities,
    pets,
    isLoadingPets,
    initialDateRange,
    initialPetIds,
    availabilities,
    priceMap,
}: BookingCheckoutFormProps) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>(
        initialDateRange ?? undefined,
    );
    const [selectedPetIds, setSelectedPetIds] = useState<string[]>(initialPetIds);
    const [specialRequests, setSpecialRequests] = useState("");
    const [paymentMethodId, setPaymentMethodId] = useState<string | null>(null);
    const { execute, isLoading: isSubmitting } = useAsyncState();

    const acceptedTypeIds = acceptedAnimalTypeIds(capacities);
    const eligiblePets = pets.filter((pet) => acceptedTypeIds.includes(pet.animalTypeId));
    const petsAvailability = resolvePetsAvailability(eligiblePets, capacities);
    const selectedPets = eligiblePets.filter((pet) => selectedPetIds.includes(pet.id));
    const from = dateRange?.from;
    const to = dateRange?.to;
    const hasDates = Boolean(from && to);
    const nights = from && to ? computeNights(from, to) : 0;
    const totals = computeBookingTotals(selectedPets, capacities, nights);
    const canSubmit = hasDates && selectedPets.length > 0 && nights > 0 && paymentMethodId !== null;

    const togglePet = (petId: string) => {
        setSelectedPetIds((current) =>
            current.includes(petId)
                ? current.filter((value) => value !== petId)
                : [...current, petId],
        );
    };

    const handleSubmit = async () => {
        if (!dateRange?.from || !dateRange?.to) {
            toast.error(t("features.bookings.checkout.selectDates"));
            return;
        }

        if (!paymentMethodId) {
            toast.error(t("features.bookings.checkout.selectPaymentMethod"));
            return;
        }

        const checkInDate = toApiDate(dateRange.from);
        const checkOutDate = toApiDate(dateRange.to);

        const result = await execute(
            () =>
                createBooking({
                    activityId: activity.id,
                    checkInDate,
                    checkOutDate,
                    petIds: selectedPets.map((pet) => pet.id),
                    specialRequests: specialRequests.trim() || undefined,
                    paymentMethodId,
                    savePaymentMethod: false,
                }),
            { displayError: true },
        );

        if (!result) return;

        if (result.paymentStatus !== "succeeded" && result.clientSecret) {
            const stripe = await stripePromise;
            if (!stripe) {
                toast.error(t("features.bookings.checkout.paymentSetupFailed"));
                return;
            }
            const actionResult = await stripe.handleNextAction({
                clientSecret: result.clientSecret,
            });
            if (actionResult.error) {
                toast.error(t("features.bookings.checkout.paymentFailed"), {
                    description: actionResult.error.message,
                });
                return;
            }
        }

        toast.success(t("features.bookings.checkout.successTitle"), {
            description: t("features.bookings.checkout.successDescription"),
        });
        router.push(routes.Explore());
    };

    const locale = typeof navigator !== "undefined" ? navigator.language : "fr-FR";
    const datesLabel = from && to ? formatDateRange(from, to, locale) : null;
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
                <ActivitySummaryCard activity={activity} />

                <BookingTripSection
                    datesLabel={datesLabel}
                    petsCountLabel={petsCountLabel}
                    dateRange={dateRange}
                    onDateRangeChange={setDateRange}
                    availabilities={availabilities}
                    priceMap={priceMap}
                />

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

                <Separator />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.paymentSection")}
                    </h2>
                    <PaymentMethodPicker value={paymentMethodId} onChange={setPaymentMethodId} />
                </section>
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
