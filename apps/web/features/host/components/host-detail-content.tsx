"use client";

import { useState } from "react";
import { ArrowLeft, Image as ImageIcon } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Separator } from "@workspace/ui/components/separator";
import { toApiDate, fromApiDate } from "@workspace/common";
import type {
    ActivityCycleSettingModel,
    ActivityModel,
    AvailabilityModel,
} from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth";
import { useOpenConversation } from "@/features/conversations/hooks/use-open-conversation";
import { DetailPageLayout } from "@/components/layouts/detail-page-layout";
import { FavoriteButton } from "@/features/activities";

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
    const { user, isAuthenticated } = useAuth();
    const { openWithActivity, isPending: isContactPending } = useOpenConversation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialDateRange);
    const pricePerNight = minPricePerNight(capacities);
    const canBook = Boolean(dateRange?.from && dateRange?.to);
    const isHost = user?.id === activity.managerId;
    const canContact = isAuthenticated && !isHost;

    const handleBook = () => {
        if (!dateRange?.from || !dateRange?.to) return;
        router.push(
            routes.HostBook({
                id: activity.id,
                search_params: {
                    check_in: toApiDate(dateRange.from),
                    check_out: toApiDate(dateRange.to),
                },
            }),
        );
    };

    const images = activity.images.map((img) => img.url);

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
                    pricePerNight={pricePerNight}
                    dateRange={dateRange}
                    canBook={canBook}
                    onBook={handleBook}
                    onContact={canContact ? () => openWithActivity(activity.id) : undefined}
                    isContactPending={isContactPending}
                />
            }
            className="bg-white pb-[140px] md:pb-20"
        >
            <div className="flex flex-col gap-1 px-4 pt-4">
                <HostHeaderSection
                    name={activity.name}
                    address={activity.address}
                    capacities={capacities}
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

                <HostAboutSection description={activity.description} />

                <Separator className="my-6" />

                <HostSpeciesSection capacities={capacities} />

                <Separator className="my-6" />

                <HostLocationSection address={activity.address} />

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
