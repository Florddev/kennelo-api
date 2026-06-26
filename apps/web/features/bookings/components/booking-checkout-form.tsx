"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { Separator } from "@workspace/ui/components/separator";
import { createBooking } from "@workspace/modules/bookings";
import { computeNights, formatDateRange, toApiDate } from "@workspace/common";
import type {
    ActivityCycleModel,
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
import {
    acceptedAnimalTypeIds,
    HostPetEstimationSelector,
    isAnimalTypeAvailableForDates,
} from "@/features/host";
import { ActivitySummaryCard } from "@/features/activities/components/activity-summary-card";
import { PaymentMethodPicker } from "@/features/payment-methods/components/payment-method-picker";

import { useBookingQuote } from "../hooks/use-booking-quote";

const stripePromise = getStripe();
import { BookingHeader } from "./booking-header";
import { BookingTripSection } from "./booking-trip-section";
import { PriceBreakdown } from "./price-breakdown";
import { BookingMessageSection } from "./booking-message-section";
import { BookingFooter } from "./booking-footer";

type BookingCheckoutFormProps = {
    activity: ActivityModel;
    capacities: ActivityCycleSettingModel[];
    pets: PetModel[];
    cycles: ActivityCycleModel[];
    initialDateRange: { from: Date; to: Date } | null;
    initialPetIds: string[];
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
};

function resolveDates(dateRange: DateRange | undefined) {
    const from = dateRange?.from;
    const to = dateRange?.to;
    return {
        from,
        to,
        hasDates: Boolean(from && to),
        nights: from && to ? computeNights(from, to) : 0,
        checkInDate: from ? toApiDate(from) : null,
        checkOutDate: to ? toApiDate(to) : null,
    };
}

function derivePetSelection(
    pets: PetModel[],
    capacities: ActivityCycleSettingModel[],
    cycles: ActivityCycleModel[],
    from: Date | undefined,
    to: Date | undefined,
    selectedPetIds: string[],
) {
    const acceptedTypeIds = acceptedAnimalTypeIds(capacities);
    const eligiblePets = pets.filter((pet) => acceptedTypeIds.includes(pet.animalTypeId));
    const disabledPetIds = eligiblePets
        .filter(
            (pet) => !isAnimalTypeAvailableForDates(pet.animalTypeId, capacities, cycles, from, to),
        )
        .map((pet) => pet.id);
    const selectedPets = eligiblePets.filter(
        (pet) => selectedPetIds.includes(pet.id) && !disabledPetIds.includes(pet.id),
    );

    return {
        eligiblePets,
        disabledPetIds,
        selectedPets,
        hiddenCount: pets.length - eligiblePets.length,
    };
}

export function BookingCheckoutForm({
    activity,
    capacities,
    pets,
    cycles,
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

    const { from, to, hasDates, nights, checkInDate, checkOutDate } = resolveDates(dateRange);
    const { eligiblePets, disabledPetIds, selectedPets, hiddenCount } = derivePetSelection(
        pets,
        capacities,
        cycles,
        from,
        to,
        selectedPetIds,
    );

    const { quote, isLoading: isQuoteLoading } = useBookingQuote({
        activityId: activity.id,
        checkInDate,
        checkOutDate,
        petIds: selectedPets.map((pet) => pet.id),
    });

    const canSubmit = Boolean(
        hasDates && selectedPets.length > 0 && nights > 0 && paymentMethodId && quote,
    );

    const togglePet = (petId: string) => {
        setSelectedPetIds((current) =>
            current.includes(petId)
                ? current.filter((value) => value !== petId)
                : [...current, petId],
        );
    };

    const handleSubmit = async () => {
        if (!checkInDate || !checkOutDate) {
            toast.error(t("features.bookings.checkout.selectDates"));
            return;
        }

        if (!paymentMethodId) {
            toast.error(t("features.bookings.checkout.selectPaymentMethod"));
            return;
        }

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
        count: selectedPets.length,
    });

    return (
        <div className="relative flex flex-col pb-[140px] md:pb-20">
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

                <HostPetEstimationSelector
                    pets={eligiblePets}
                    selectedPetIds={selectedPetIds}
                    onToggle={togglePet}
                    disabledPetIds={disabledPetIds}
                    hiddenCount={hiddenCount}
                    emptyHref={routes.MyPets()}
                />

                <Separator />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.priceSection")}
                    </h2>
                    <PriceBreakdown quote={quote} isLoading={isQuoteLoading} />
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
                total={quote?.totalPrice ?? 0}
                canSubmit={canSubmit}
                isSubmitting={isSubmitting}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
