"use client";

import { useTranslations } from "next-intl";
import { Inbox } from "lucide-react";
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    EmptyDescription,
} from "@workspace/ui/components/empty";

import { useEstablishmentBookings } from "@/features/bookings/hooks/use-establishment-bookings";
import { ReservationReviewCard } from "./reservation-review-card";

type ReservationReviewListProps = {
    establishmentId: string;
};

export function ReservationReviewList({ establishmentId }: ReservationReviewListProps) {
    const t = useTranslations();
    const { bookings, isLoading } = useEstablishmentBookings(establishmentId, {
        status: "pending",
    });

    if (isLoading) {
        return (
            <div className="flex flex-col gap-2">
                {[0, 1, 2].map((i) => (
                    <div key={i} className="h-28 animate-pulse rounded-2xl bg-muted" />
                ))}
            </div>
        );
    }

    if (bookings.length === 0) {
        return (
            <Empty className="rounded-2xl border py-8">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Inbox />
                    </EmptyMedia>
                    <EmptyTitle>{t("features.bookings.review.emptyTitle")}</EmptyTitle>
                    <EmptyDescription>
                        {t("features.bookings.review.emptyDescription")}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <section data-slot="reservation-review-list" className="flex flex-col gap-2">
            <h2 className="text-lg font-semibold text-foreground">
                {t("features.bookings.review.title")}
            </h2>
            {bookings.map((booking) => (
                <ReservationReviewCard
                    key={booking.id}
                    booking={booking}
                    establishmentId={establishmentId}
                />
            ))}
        </section>
    );
}
