"use client";

import { useMemo, useState } from "react";
import { ArrowLeft, Image as ImageIcon } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import { Sticky } from "@workspace/ui/components/sticky";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";
import { toApiDate, fromApiDate } from "@workspace/common";
import type {
    ActivityCycleSettingModel,
    ActivityModel,
    AvailabilityModel,
} from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { useNavigation } from "@/hooks/use-navigation";
import { useIsMobile } from "@/hooks/use-mobile";
import { useAuth } from "@/features/auth";
import { usePets } from "@/features/pets/hooks/use-pets";
import { useOpenConversation } from "@/features/conversations/hooks/use-open-conversation";
import { useHostReviews } from "../hooks/use-host-reviews";
import { useHostPriceCalendar } from "../hooks/use-host-price-calendar";
import { useHostAnimalTypePrices } from "../hooks/use-host-animal-type-prices";
import { useHostPublicCycles } from "../hooks/use-host-public-cycles";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import { FavoriteButton } from "@/features/activities";

import { HostHeaderSection } from "./host-header-section";
import { HostManagerSection } from "./host-manager-section";
import { HostVerifiedBanner } from "./host-verified-banner";
import { HostSpeciesSection } from "./host-species-section";
import { HostAboutSection } from "./host-about-section";
import { HostLocationSection } from "./host-location-section";
import { HostDateSection } from "./host-date-section";
import { HostPetEstimationSelector } from "./host-pet-estimation-selector";
import { HostAnimalTypeEstimationSelector } from "./host-animal-type-estimation-selector";
import { HostCyclePricingSection } from "./host-cycle-pricing-section";
import { HostReviewsSection } from "./host-reviews-section";
import { HostBookingBar } from "./host-booking-bar";
import { HostDetailDesktop } from "./host-detail-desktop";

import { acceptedAnimalTypeIds, buildPetPriceMap } from "../lib/pricing";

type HostDetailContentProps = {
    activity: ActivityModel;
    capacities: ActivityCycleSettingModel[];
    availabilities: AvailabilityModel[];
    initialDateRange: DateRange | undefined;
    onBack: () => void;
};

export function HostDetailContent({
    activity,
    capacities,
    availabilities,
    initialDateRange,
    onBack,
}: HostDetailContentProps) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const isMobile = useIsMobile();
    const { user, isAuthenticated } = useAuth();
    const { openWithActivity, isPending: isContactPending } = useOpenConversation();
    const {
        reviews,
        averageRating,
        reviewCount,
        isLoading: areReviewsLoading,
    } = useHostReviews(activity.id);
    const { priceMap } = useHostPriceCalendar(activity.id);
    const { priceRanges, isLoading: areSpeciesPricesLoading } = useHostAnimalTypePrices(
        activity.id,
    );
    const { cycles, isLoading: areCyclesLoading } = useHostPublicCycles(activity.id);
    const { pets } = usePets({ enabled: isAuthenticated });
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialDateRange);
    const [selectedPetIds, setSelectedPetIds] = useState<string[]>([]);
    const [animalTypeCounts, setAnimalTypeCounts] = useState<Record<string, number>>({});
    const isHost = user?.id === activity.managerId;

    const eligiblePets = useMemo(() => {
        const acceptedTypeIds = acceptedAnimalTypeIds(capacities);
        return pets.filter((pet) => acceptedTypeIds.includes(pet.animalTypeId));
    }, [pets, capacities]);

    const usePetSelector = isAuthenticated && eligiblePets.length > 0;

    const togglePet = (petId: string) => {
        setSelectedPetIds((current) =>
            current.includes(petId)
                ? current.filter((value) => value !== petId)
                : [...current, petId],
        );
    };

    const setAnimalTypeCount = (animalTypeId: string, count: number) => {
        setAnimalTypeCounts((current) => {
            const next = { ...current };
            if (count <= 0) {
                delete next[animalTypeId];
            } else {
                next[animalTypeId] = count;
            }
            return next;
        });
    };

    const selectedAnimalTypeIds = useMemo(() => {
        if (usePetSelector) {
            return eligiblePets
                .filter((pet) => selectedPetIds.includes(pet.id))
                .map((pet) => pet.animalTypeId);
        }
        return Object.entries(animalTypeCounts).flatMap(([animalTypeId, count]) =>
            Array.from({ length: count }, () => animalTypeId),
        );
    }, [usePetSelector, eligiblePets, selectedPetIds, animalTypeCounts]);

    const effectivePriceMap = useMemo(
        () => buildPetPriceMap(priceMap, selectedAnimalTypeIds, cycles),
        [priceMap, cycles, selectedAnimalTypeIds],
    );
    const canContact = isAuthenticated && !isHost;

    const handleBook = () => {
        const params: Record<string, string> = {};
        if (dateRange?.from && dateRange?.to) {
            params.check_in = toApiDate(dateRange.from);
            params.check_out = toApiDate(dateRange.to);
        }
        if (selectedPetIds.length > 0) {
            params.pet_ids = selectedPetIds.join(",");
        }
        router.push(
            routes.HostBook({
                id: activity.id,
                search_params: Object.keys(params).length > 0 ? params : undefined,
            }),
        );
    };

    const images = activity.images.map((img) => img.url);
    const contactHandler = canContact ? () => openWithActivity(activity.id) : undefined;

    const galleryEmptyState = (
        <div className="flex aspect-[4/3] w-full items-center justify-center bg-muted">
            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                <ImageIcon className="size-10" />
                <span className="text-sm">{t("features.host.detail.noPhotos")}</span>
            </div>
        </div>
    );

    if (!isMobile) {
        return (
            <HostDetailDesktop
                activity={activity}
                images={images}
                galleryEmptyState={galleryEmptyState}
                averageRating={averageRating}
                reviewCount={reviewCount}
                reviews={reviews}
                areReviewsLoading={areReviewsLoading}
                capacities={capacities}
                priceRanges={priceRanges}
                areSpeciesPricesLoading={areSpeciesPricesLoading}
                cycles={cycles}
                areCyclesLoading={areCyclesLoading}
                availabilities={availabilities}
                dateRange={dateRange}
                onDateRangeChange={setDateRange}
                priceMap={effectivePriceMap}
                usePetSelector={usePetSelector}
                eligiblePets={eligiblePets}
                selectedPetIds={selectedPetIds}
                onTogglePet={togglePet}
                animalTypeCounts={animalTypeCounts}
                onAnimalTypeCountChange={setAnimalTypeCount}
                onBook={handleBook}
                onContact={contactHandler}
                isContactPending={isContactPending}
                onBack={onBack}
            />
        );
    }

    return (
        <DetailPageLayout
            images={images}
            altPrefix={activity.name}
            emptyState={galleryEmptyState}
            desktopCtaLabel={t("features.host.detail.viewPhotos", { count: images.length })}
            headerStart={
                <Button size="icon-sm" className="text-primary bg-card" onClick={onBack}>
                    <ArrowLeft />
                </Button>
            }
            headerEnd={
                <FavoriteButton activityId={activity.id} isFavorited={activity.isFavorited} />
            }
            footer={
                <HostBookingBar
                    priceMap={effectivePriceMap}
                    dateRange={dateRange}
                    onBook={handleBook}
                    onContact={contactHandler}
                    isContactPending={isContactPending}
                />
            }
            className="bg-white pb-20"
        >
            <div className="flex flex-col gap-1 px-4 pt-4">
                <HostHeaderSection
                    name={activity.name}
                    address={activity.address}
                    capacities={capacities}
                    averageRating={averageRating}
                    reviewCount={reviewCount}
                />
                {activity.manager && (
                    <>
                        <HostManagerSection manager={activity.manager} />
                        {activity.manager.isIdVerified && (
                            <section className="pt-4">
                                <HostVerifiedBanner />
                            </section>
                        )}
                    </>
                )}
            </div>

            <Tabs defaultValue="general" className="flex flex-col gap-0 pt-2">
                <Sticky
                    top={0}
                    className="bg-white w-full z-20"
                    stickyClassName="border-b border-border/30"
                >
                    <TabsList variant="line" className="w-full">
                        <div className="grid w-full grid-cols-3 px-4 py-2">
                            <div className="flex justify-start">
                                <TabsTrigger value="general" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.host.detail.tabs.general")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-center">
                                <TabsTrigger value="pricing" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.host.detail.tabs.pricing")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-end">
                                <TabsTrigger value="reviews" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.host.detail.tabs.reviews")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                        </div>
                    </TabsList>
                </Sticky>

                <Separator className="bg-border/50" />

                <TabsContent value="general" className="flex flex-col gap-6 px-4">
                    <HostAboutSection activity={activity} />
                    <Separator />
                    <HostSpeciesSection
                        priceRanges={priceRanges}
                        isLoading={areSpeciesPricesLoading}
                    />
                    <Separator />
                    <HostLocationSection address={activity.address} />
                </TabsContent>

                <TabsContent value="pricing" className="flex flex-col gap-6 p-4">
                    {usePetSelector ? (
                        <HostPetEstimationSelector
                            pets={eligiblePets}
                            selectedPetIds={selectedPetIds}
                            onToggle={togglePet}
                        />
                    ) : (
                        <HostAnimalTypeEstimationSelector
                            priceRanges={priceRanges}
                            counts={animalTypeCounts}
                            onCountChange={setAnimalTypeCount}
                        />
                    )}
                    <HostDateSection
                        dateRange={dateRange}
                        onDateRangeChange={setDateRange}
                        availabilities={availabilities}
                        priceMap={effectivePriceMap}
                    />
                    <Separator />
                    <HostCyclePricingSection cycles={cycles} isLoading={areCyclesLoading} />
                </TabsContent>

                <TabsContent value="reviews" className="p-4">
                    <HostReviewsSection
                        reviews={reviews}
                        averageRating={averageRating}
                        reviewCount={reviewCount}
                        isLoading={areReviewsLoading}
                    />
                </TabsContent>
            </Tabs>
        </DetailPageLayout>
    );
}

export function parseDateRangeFromParams(
    checkIn: string | null,
    checkOut: string | null,
): DateRange | undefined {
    if (!checkIn || !checkOut) return undefined;
    try {
        return { from: fromApiDate(checkIn), to: fromApiDate(checkOut) };
    } catch {
        return undefined;
    }
}
