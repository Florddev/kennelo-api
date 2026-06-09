"use client";

import Link from "next/link";
import Image from "next/image";
import { useLocale, useTranslations } from "next-intl";

import { formatDateRangeCompact } from "@workspace/common";
import type { MessageModel } from "@workspace/modules/conversations";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Button } from "@workspace/ui/components/button";

import { useNavigation } from "@/hooks/use-navigation";
import { StatusDot } from "./status-dot";
import { getStatusLabel } from "../lib/utils";

function BookingConfirmationCard({
    activityName,
    dateRange,
    avatarUrl,
    initials,
    isCancelled,
    statusLabel,
    viewDetailsLabel,
    href,
}: {
    activityName: string;
    dateRange: string;
    avatarUrl: string | null;
    initials: string;
    isCancelled: boolean;
    statusLabel: string;
    viewDetailsLabel: string;
    href: string;
}) {
    return (
        <>
            <div className="flex gap-2 items-center">
                <StatusDot isCancelled={isCancelled} />
                <p className="text-xs text-muted-foreground">{statusLabel}</p>
            </div>
            <div className="flex justify-between">
                <div className="flex flex-col gap-0.5">
                    <p className="text-sm font-semibold truncate">{activityName}</p>
                    <span className="text-xs text-muted-foreground">{dateRange}</span>
                </div>
                {isCancelled && (
                    <Avatar size="sm">
                        {avatarUrl && (
                            <AvatarImage src={avatarUrl} alt={initials} className="rounded-[6px]" />
                        )}
                        <AvatarFallback className="text-xs">{initials}</AvatarFallback>
                    </Avatar>
                )}
            </div>
            {!isCancelled && (
                <>
                    {avatarUrl && (
                        <div className="aspect-video relative w-full overflow-hidden">
                            <Image
                                src={avatarUrl}
                                alt={activityName}
                                fill
                                className="object-cover rounded-sm"
                            />
                        </div>
                    )}
                    <Button size="sm" variant="flat" className="w-full" asChild>
                        <Link href={href}>{viewDetailsLabel}</Link>
                    </Button>
                </>
            )}
        </>
    );
}

function BookingRequestCard({
    activityName,
    avatarUrl,
    initials,
    statusLabel,
    viewDetailsLabel,
    href,
}: {
    activityName: string;
    avatarUrl: string | null;
    initials: string;
    statusLabel: string;
    viewDetailsLabel: string;
    href: string;
}) {
    return (
        <>
            <div className="flex items-center justify-between gap-2.5">
                <div className="flex flex-col min-w-0">
                    <p className="text-xs text-muted-foreground">{statusLabel}</p>
                    <p className="text-sm font-semibold truncate">{activityName}</p>
                </div>
                <Avatar size="sm">
                    {avatarUrl && (
                        <AvatarImage src={avatarUrl} alt={initials} className="rounded-[6px]" />
                    )}
                    <AvatarFallback className="text-xs">{initials}</AvatarFallback>
                </Avatar>
            </div>
            <Button size="sm" variant="flat" className="w-full" asChild>
                <Link href={href}>{viewDetailsLabel}</Link>
            </Button>
        </>
    );
}

export function BookingReferenceCard({ message }: { message: MessageModel }) {
    const t = useTranslations();
    const locale = useLocale();
    const { routes } = useNavigation();

    const booking = message.booking;
    const activity = booking?.activity ?? null;
    const avatarUrl = activity?.getAvatarUrl() ?? null;
    const activityName = activity?.name ?? "—";
    const initials = activityName.slice(0, 2).toUpperCase();
    const isActivity = message.senderType === "activity";
    const isCancelled = booking?.isCancelled() ?? false;
    const statusLabel = getStatusLabel(t, isActivity, isCancelled);
    const viewDetailsLabel = t("features.conversations.bookingReference.viewDetails");
    const href = booking ? routes.BookingDetail({ id: booking.id }) : "#";

    return (
        <div
            data-slot="booking-reference-card"
            className="rounded-2xl border border-border/80 bg-card p-3 flex flex-col gap-2 w-56"
        >
            {isActivity && booking ? (
                <BookingConfirmationCard
                    activityName={activityName}
                    dateRange={formatDateRangeCompact(
                        booking.checkInDate,
                        booking.checkOutDate,
                        locale,
                    )}
                    avatarUrl={avatarUrl}
                    initials={initials}
                    isCancelled={isCancelled}
                    statusLabel={statusLabel}
                    viewDetailsLabel={viewDetailsLabel}
                    href={href}
                />
            ) : (
                <BookingRequestCard
                    activityName={activityName}
                    avatarUrl={avatarUrl}
                    initials={initials}
                    statusLabel={statusLabel}
                    viewDetailsLabel={viewDetailsLabel}
                    href={href}
                />
            )}
        </div>
    );
}
