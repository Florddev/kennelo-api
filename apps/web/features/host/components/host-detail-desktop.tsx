"use client";

import type { ReactNode } from "react";

import { ArrowLeft } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import type {
    ActivityCycleModel,
    ActivityCycleSettingModel,
    ActivityModel,
    AnimalTypePriceRangeModel,
    AvailabilityModel,
    PriceCalendar,
} from "@workspace/modules/activities";
import type { PetModel } from "@workspace/modules/pets";
import type { ReviewModel } from "@workspace/modules/reviews";
import type { DateRange } from "react-day-picker";

import { MediaGallery } from "@/components/media/media-gallery";
import { FavoriteButton } from "@/features/activities";

import { HostHeaderSection } from "./host-header-section";
import { HostManagerSection } from "./host-manager-section";
import { HostVerifiedBanner } from "./host-verified-banner";
import { HostAboutSection } from "./host-about-section";
import { HostSpeciesSection } from "./host-species-section";
import { HostLocationSection } from "./host-location-section";
import { HostCyclePricingSection } from "./host-cycle-pricing-section";
import { HostReviewsSection } from "./host-reviews-section";
import { HostPetEstimationSelector } from "./host-pet-estimation-selector";
import { HostAnimalTypeEstimationSelector } from "./host-animal-type-estimation-selector";
import { HostBookingBox } from "./host-booking-box";

type HostDetailDesktopProps = {
    activity: ActivityModel;
    images: string[];
    galleryEmptyState: ReactNode;
    averageRating: number | null;
    reviewCount: number;
    reviews: ReviewModel[];
    areReviewsLoading: boolean;
    capacities: ActivityCycleSettingModel[];
    priceRanges: AnimalTypePriceRangeModel[];
    areSpeciesPricesLoading: boolean;
    cycles: ActivityCycleModel[];
    areCyclesLoading: boolean;
    availabilities: AvailabilityModel[];
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    priceMap: PriceCalendar;
    usePetSelector: boolean;
    eligiblePets: PetModel[];
    selectedPetIds: string[];
    onTogglePet: (petId: string) => void;
    animalTypeCounts: Record<string, number>;
    onAnimalTypeCountChange: (animalTypeId: string, count: number) => void;
    onBook: () => void;
    onContact?: () => void;
    isContactPending?: boolean;
    onBack: () => void;
};

export function HostDetailDesktop({
    activity,
    images,
    galleryEmptyState,
    averageRating,
    reviewCount,
    reviews,
    areReviewsLoading,
    capacities,
    priceRanges,
    areSpeciesPricesLoading,
    cycles,
    areCyclesLoading,
    availabilities,
    dateRange,
    onDateRangeChange,
    priceMap,
    usePetSelector,
    eligiblePets,
    selectedPetIds,
    onTogglePet,
    animalTypeCounts,
    onAnimalTypeCountChange,
    onBook,
    onContact,
    isContactPending,
    onBack,
}: HostDetailDesktopProps) {
    const t = useTranslations();

    const selector = usePetSelector ? (
        <HostPetEstimationSelector
            pets={eligiblePets}
            selectedPetIds={selectedPetIds}
            onToggle={onTogglePet}
            hideHeader
        />
    ) : (
        <HostAnimalTypeEstimationSelector
            priceRanges={priceRanges}
            counts={animalTypeCounts}
            onCountChange={onAnimalTypeCountChange}
            hideHeader
        />
    );

    const selectedAnimalCount = usePetSelector
        ? selectedPetIds.length
        : Object.values(animalTypeCounts).reduce((sum, count) => sum + count, 0);

    return (
        <div
            data-slot="host-detail-desktop"
            className="container mx-auto flex flex-col gap-6 px-4 py-6 md:px-6"
        >
            <div className="flex items-center justify-between">
                <Button variant="flat" onClick={onBack}>
                    <ArrowLeft className="size-4" />
                    {t("common.actions.back")}
                </Button>
                <FavoriteButton activityId={activity.id} isFavorited={activity.isFavorited} />
            </div>

            <MediaGallery
                images={images}
                altPrefix={activity.name}
                emptyState={galleryEmptyState}
                desktopCtaLabel={t("features.host.detail.viewPhotos", { count: images.length })}
            />

            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div className="flex flex-col gap-6 lg:col-span-2">
                    <HostHeaderSection
                        name={activity.name}
                        address={activity.address}
                        capacities={capacities}
                        averageRating={averageRating}
                        reviewCount={reviewCount}
                    />

                    {activity.manager && (
                        <div className="flex flex-col gap-4">
                            <HostManagerSection manager={activity.manager} />
                            {activity.manager.isIdVerified && <HostVerifiedBanner />}
                        </div>
                    )}

                    <Separator />
                    <HostAboutSection activity={activity} />

                    <Separator />
                    <HostSpeciesSection
                        priceRanges={priceRanges}
                        isLoading={areSpeciesPricesLoading}
                    />

                    <Separator />
                    <HostLocationSection address={activity.address} />

                    <Separator />
                    <HostCyclePricingSection cycles={cycles} isLoading={areCyclesLoading} />

                    <Separator />
                    <HostReviewsSection
                        reviews={reviews}
                        averageRating={averageRating}
                        reviewCount={reviewCount}
                        isLoading={areReviewsLoading}
                    />
                </div>

                <div className="lg:col-span-1">
                    <div className="sticky top-[calc(var(--header-height)+1.5rem)] max-h-[calc(100dvh-var(--header-height)-3rem)] overflow-y-auto">
                        <HostBookingBox
                            selector={selector}
                            usePetSelector={usePetSelector}
                            selectedAnimalCount={selectedAnimalCount}
                            dateRange={dateRange}
                            onDateRangeChange={onDateRangeChange}
                            availabilities={availabilities}
                            priceMap={priceMap}
                            onBook={onBook}
                            onContact={onContact}
                            isContactPending={isContactPending}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
