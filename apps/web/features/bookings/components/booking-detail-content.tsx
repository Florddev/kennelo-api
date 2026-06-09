"use client";

import type { BookingModel } from "@workspace/modules/bookings";
import type { PetModel } from "@workspace/modules/pets";

import { useAuth } from "@/features/auth";
import { usePlatform } from "@/hooks/use-platform";

import { SplitPageLayout } from "@/components/layouts/split-page-layout";

import { BookingDetailHeader } from "./booking-detail-header";
import { BookingDetailIdentity } from "./booking-detail-identity";
import { BookingDetailMap } from "./booking-detail-map";
import { BookingDetailTabs } from "./booking-detail-tabs";

export function BookingDetailContent({
    booking,
    userPets = [],
    onBack,
}: {
    booking: BookingModel;
    userPets?: PetModel[];
    onBack: () => void;
}) {
    const { user } = useAuth();
    const { isNative } = usePlatform();
    const isHost = !!user && user.id === booking.activity?.manager?.id;
    const petMap = new Map(userPets.map((p) => [p.id, p]));

    const {
        name: activityName = "",
        images = [],
        address = null,
        manager = null,
        phone = null,
        email = null,
        avatarUrl = null,
    } = booking.activity ?? {};

    const imageUrls = [...(avatarUrl ? [avatarUrl] : []), ...images.map((img) => img.url)];

    return (
        <SplitPageLayout isRoot={false}>
            <SplitPageLayout.Sidebar className="md:p-0 md:overflow-y-auto">
                <div className="md:mx-4">
                    <BookingDetailHeader
                        imageUrls={imageUrls}
                        activityName={activityName}
                        onBack={onBack}
                        isCapacitorApp={isNative}
                    />
                </div>

                <div className="bg-card">
                    <BookingDetailIdentity
                        booking={booking}
                        activityName={activityName}
                        address={address}
                    />

                    <BookingDetailTabs
                        booking={booking}
                        petMap={petMap}
                        address={address}
                        manager={manager}
                        activityName={activityName}
                        phone={phone}
                        email={email}
                        isHost={isHost}
                    />
                </div>
            </SplitPageLayout.Sidebar>

            <SplitPageLayout.Content
                cleanContainer
                className="hidden md:block md:p-0 md:overflow-hidden"
            >
                <div className="h-[calc(100dvh-var(--header-height))]">
                    <BookingDetailMap address={address} />
                </div>
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}
