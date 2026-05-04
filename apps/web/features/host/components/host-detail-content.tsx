"use client";

import { useState } from "react";
import { ArrowLeft, Image as ImageIcon } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import { toApiDate, fromApiDate } from "@workspace/common";
import type {
    CapacityModel,
    EstablishmentModel,
    AvailabilityModel,
} from "@workspace/modules/establishments";
import type { DateRange } from "react-day-picker";

import { useNavigation } from "@/hooks/use-navigation";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";

import { minPricePerNight } from "../lib/pricing";
import { HostHeaderSection } from "./host-header-section";
import { HostManagerSection } from "./host-manager-section";
import { HostVerifiedBanner } from "./host-verified-banner";
import { HostSpeciesSection } from "./host-species-section";
import { HostAboutSection } from "./host-about-section";
import { HostLocationSection } from "./host-location-section";
import { HostDateSection } from "./host-date-section";
import { HostReviewsSection } from "./host-reviews-section";
import { HostBookingBar } from "./host-booking-bar";

type HostDetailContentProps = {
    establishment: EstablishmentModel;
    capacities: CapacityModel[];
    availabilities: AvailabilityModel[];
    initialDateRange: DateRange | undefined;
    onBack: () => void;
};

export function HostDetailContent({
    establishment,
    capacities,
    availabilities,
    initialDateRange,
    onBack,
}: HostDetailContentProps) {
    const t = useTranslations();
    const { router, routes } = useNavigation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialDateRange);
    const pricePerNight = minPricePerNight(capacities);
    const canBook = Boolean(dateRange?.from && dateRange?.to);

    const handleBook = () => {
        if (!dateRange?.from || !dateRange?.to) return;
        router.push(
            routes.HostBook({
                id: establishment.id,
                search_params: {
                    check_in: toApiDate(dateRange.from),
                    check_out: toApiDate(dateRange.to),
                },
            }),
        );
    };

    const images = establishment.images.map((img) => img.url);

    const galleryEmptyState = (
        <div className="flex aspect-[4/3] w-full items-center justify-center bg-muted">
            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                <ImageIcon className="size-10" />
                <span className="text-sm">{t("features.host.detail.noPhotos")}</span>
            </div>
        </div>
    );

    return (
        <DetailPageLayout
            images={images}
            altPrefix={establishment.name}
            emptyState={galleryEmptyState}
            desktopCtaLabel={t("features.host.detail.viewPhotos", { count: images.length })}
            headerStart={
                <Button size="icon-sm" className="text-primary bg-card" onClick={onBack}>
                    <ArrowLeft />
                </Button>
            }
            footer={
                <HostBookingBar
                    pricePerNight={pricePerNight}
                    dateRange={dateRange}
                    canBook={canBook}
                    onBook={handleBook}
                />
            }
            className="bg-white pb-[140px] md:pb-20"
        >
            <div className="flex flex-col gap-1 px-4 pt-4">
                <HostHeaderSection
                    name={establishment.name}
                    address={establishment.address}
                    capacities={capacities}
                />
                {establishment.manager && (
                    <>
                        <HostManagerSection manager={establishment.manager} />
                        {establishment.manager.isIdVerified && (
                            <section className="pt-4">
                                <HostVerifiedBanner />
                            </section>
                        )}
                    </>
                )}

                <HostAboutSection description={establishment.description} />

                <Separator className="my-6" />

                <HostSpeciesSection capacities={capacities} />

                <Separator className="my-6" />

                <HostLocationSection address={establishment.address} />

                <Separator className="my-6" />

                <HostDateSection
                    dateRange={dateRange}
                    onDateRangeChange={setDateRange}
                    availabilities={availabilities}
                />

                <Separator className="my-6" />

                <HostReviewsSection reviews={[]} />
            </div>
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
