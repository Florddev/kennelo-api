"use client";

import { useTranslations } from "next-intl";

import { Separator } from "@workspace/ui/components/separator";
import { Sticky } from "@workspace/ui/components/sticky";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@workspace/ui/components/tabs";

import type { BookingModel } from "@workspace/modules/bookings";
import type { PetModel } from "@workspace/modules/pets";
import type { AddressModel } from "@workspace/modules/address";
import type { UserModel } from "@workspace/modules/users";

import { BookingDetailTabDetails } from "./booking-detail-tab-details";
import { BookingDetailTabLocation } from "./booking-detail-tab-location";
import { BookingDetailTabSupport } from "./booking-detail-tab-support";

export function BookingDetailTabs({
    booking,
    petMap,
    address,
    manager,
    activityName,
    phone,
    email,
    isHost = false,
}: {
    booking: BookingModel;
    petMap: Map<string, PetModel>;
    address: AddressModel | null;
    manager: UserModel | null;
    activityName?: string | null;
    phone?: string | null;
    email?: string | null;
    isHost?: boolean;
}) {
    const t = useTranslations();

    return (
        <>
            <Separator className="opacity-50 mx-4" />

            <Tabs defaultValue="details" className="flex flex-col gap-0">
                <Sticky
                    top={0}
                    className="bg-card w-full z-20"
                    stickyClassName="border-b border-border/30"
                >
                    <TabsList variant="line" className="w-full">
                        <div className="grid grid-cols-3 w-full py-2 px-4">
                            <div className="flex justify-start">
                                <TabsTrigger value="details" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.bookings.detail.tabDetails")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-center">
                                <TabsTrigger value="location" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.bookings.detail.tabLocation")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                            <div className="flex justify-end">
                                <TabsTrigger value="support" className="max-w-fit">
                                    <span data-slot="tab-label" className="!text-base">
                                        {t("features.bookings.detail.tabSupport")}
                                    </span>
                                    <span data-slot="tab-indicator" />
                                </TabsTrigger>
                            </div>
                        </div>
                    </TabsList>
                </Sticky>

                <TabsContent value="details">
                    <BookingDetailTabDetails booking={booking} petMap={petMap} isHost={isHost} />
                </TabsContent>

                <TabsContent value="location">
                    <BookingDetailTabLocation address={address} />
                </TabsContent>

                <TabsContent value="support">
                    <BookingDetailTabSupport
                        manager={manager}
                        activityName={activityName}
                        phone={phone}
                        email={email}
                    />
                </TabsContent>
            </Tabs>
        </>
    );
}
