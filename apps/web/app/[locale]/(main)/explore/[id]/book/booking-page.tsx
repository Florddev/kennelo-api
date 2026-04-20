"use client";

import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { ArrowLeft } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Textarea } from "@workspace/ui/components/textarea";
import { Separator } from "@workspace/ui/components/separator";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { cn } from "@workspace/ui/lib/utils";
import { createBooking } from "@workspace/modules/bookings";

import { useNavigation } from "@/hooks/use-navigation";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth";
import { usePets } from "@/features/pets/hooks/use-pets";
import { useExploreEstablishment, demoFallbackPrice } from "@/features/explore";
import {
    PriceBreakdown,
    PetCheckboxList,
    TripSummaryCard,
    computeNightsBetween,
    computePriceBreakdown,
    toApiDate,
    fromApiDate,
} from "@/features/bookings";

export default function BookingPage() {
    const { router, routes, params } = useNavigation<{ id: string }>();
    const searchParams = useSearchParams();
    const id = params.id ?? "";
    const fromParam = searchParams.get("from");
    const toParam = searchParams.get("to");
    const { isAuthenticated, isLoaded } = useAuth();

    const { establishment, isLoading } = useExploreEstablishment(id);

    const dateRange = useMemo(() => {
        if (!fromParam || !toParam) return null;
        try {
            return { from: fromApiDate(fromParam), to: fromApiDate(toParam) };
        } catch {
            return null;
        }
    }, [fromParam, toParam]);

    if (isLoaded && !isAuthenticated) {
        router.replace(routes.Login());
        return null;
    }

    if (isLoading || !establishment || !dateRange) {
        return <CheckoutSkeleton />;
    }

    return <BookingCheckoutForm establishment={establishment} dateRange={dateRange} />;
}

function BookingCheckoutForm({
    establishment,
    dateRange,
}: {
    establishment: NonNullable<ReturnType<typeof useExploreEstablishment>["establishment"]>;
    dateRange: { from: Date; to: Date };
}) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const { pets, isLoading: isLoadingPets } = usePets();
    const [selectedPetIds, setSelectedPetIds] = useState<string[]>([]);
    const [specialRequests, setSpecialRequests] = useState("");
    const { execute, isLoading: isSubmitting } = useAsyncState();

    const pricePerNight = demoFallbackPrice(establishment.id);
    const nights = computeNightsBetween(dateRange.from, dateRange.to);
    const breakdown = computePriceBreakdown(pricePerNight, nights);
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
    const datesLabel = t("features.bookings.checkout.datesValue", {
        from: formatDay(dateRange.from, locale),
        to: formatDay(dateRange.to, locale),
    });

    return (
        <div className="relative flex flex-col bg-background pb-[140px] md:pb-20">
            <Header title={t("features.bookings.checkout.title")} onBack={() => router.back()} />

            <div className="flex flex-col gap-6 px-4 py-4">
                <TripSummaryCard establishment={establishment} />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.tripSection")}
                    </h2>
                    <InfoRow
                        label={t("features.bookings.checkout.datesLabel")}
                        value={datesLabel}
                    />
                    <InfoRow
                        label={t("features.bookings.checkout.petsLabel")}
                        value={t("features.bookings.checkout.petsCount", {
                            count: selectedPetIds.length,
                        })}
                    />
                </section>

                <Separator />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.selectPets")}
                    </h2>
                    <PetsSection
                        pets={pets}
                        selectedIds={selectedPetIds}
                        isLoading={isLoadingPets}
                        onToggle={togglePet}
                        managePetsHref={routes.MyPets()}
                    />
                </section>

                <Separator />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {t("features.bookings.checkout.priceSection")}
                    </h2>
                    <PriceBreakdown breakdown={breakdown} />
                </section>

                <Separator />

                <MessageSection value={specialRequests} onChange={setSpecialRequests} />
            </div>

            <BookingFooter
                total={breakdown.total}
                canSubmit={canSubmit && !isSubmitting}
                isSubmitting={isSubmitting}
                onSubmit={handleSubmit}
            />
        </div>
    );
}

function PetsSection({
    pets,
    selectedIds,
    isLoading,
    onToggle,
    managePetsHref,
}: {
    pets: ReturnType<typeof usePets>["pets"];
    selectedIds: string[];
    isLoading: boolean;
    onToggle: (petId: string) => void;
    managePetsHref: string;
}) {
    if (isLoading) {
        return (
            <div className="flex flex-col gap-2">
                <Skeleton className="h-16 rounded-2xl" />
                <Skeleton className="h-16 rounded-2xl" />
            </div>
        );
    }
    return (
        <PetCheckboxList
            pets={pets}
            selectedIds={selectedIds}
            onToggle={onToggle}
            managePetsHref={managePetsHref}
        />
    );
}

function MessageSection({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <div className="flex items-baseline gap-2">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.bookings.checkout.messageSection")}
                </h2>
                <span className="text-xs text-muted-foreground">
                    {t("features.bookings.checkout.messageOptional")}
                </span>
            </div>
            <Textarea
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={t("features.bookings.checkout.messagePlaceholder")}
                rows={5}
                maxLength={1000}
                className="resize-none rounded-2xl"
            />
        </section>
    );
}

function Header({ title, onBack }: { title: string; onBack: () => void }) {
    return (
        <header className="sticky top-0 z-10 flex items-center gap-3 border-b bg-background px-4 py-3">
            <button
                type="button"
                onClick={onBack}
                aria-label="Back"
                className="flex size-10 items-center justify-center rounded-full hover:bg-muted"
            >
                <ArrowLeft className="size-5" />
            </button>
            <h1 className="text-lg font-semibold">{title}</h1>
        </header>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-foreground">{label}</span>
            <span className="text-sm text-muted-foreground">{value}</span>
        </div>
    );
}

function BookingFooter({
    total,
    canSubmit,
    isSubmitting,
    onSubmit,
}: {
    total: number;
    canSubmit: boolean;
    isSubmitting: boolean;
    onSubmit: () => void;
}) {
    const t = useTranslations();

    return (
        <div
            className={cn(
                "fixed inset-x-0 z-20 border-t bg-background px-4 py-3",
                "bottom-13 md:bottom-0",
            )}
        >
            <div className="container mx-auto flex h-full items-center justify-between gap-4">
                <p className="text-sm font-semibold text-slate-900">
                    {t("features.bookings.checkout.confirmFooterTotal", { amount: total })}
                </p>
                <Button
                    onClick={onSubmit}
                    disabled={!canSubmit}
                    className="h-12 rounded-full bg-foreground px-8 text-base font-medium text-background hover:bg-foreground/90 disabled:opacity-50"
                >
                    {isSubmitting ? "…" : t("features.bookings.checkout.confirmCta")}
                </Button>
            </div>
        </div>
    );
}

function formatDay(date: Date, locale: string): string {
    return new Intl.DateTimeFormat(locale, { day: "numeric", month: "short" }).format(date);
}

function CheckoutSkeleton() {
    return (
        <div className="flex flex-col gap-4 p-4">
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-20 w-full rounded-2xl" />
            <Skeleton className="h-32 w-full rounded-2xl" />
            <Skeleton className="h-40 w-full rounded-2xl" />
        </div>
    );
}
