"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { Star, Card as CardIcon, CloseCircle, PenNewSquare, ChatRound } from "@solar-icons/react";

import type { BookingModel } from "@workspace/modules/bookings";
import type { PetModel } from "@workspace/modules/pets";
import type { UserModel } from "@workspace/modules/users";

import { NavRow } from "@/components/navigation/nav-row";
import { ShapeMedia } from "@/components/media/shape-media";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useNavigation } from "@/hooks/use-navigation";
import { useOpenConversation } from "@/features/conversations/hooks/use-open-conversation";
import { useClientCancelDialog } from "../hooks/use-client-cancel-dialog";
import { BookingDetailHostActions } from "./booking-detail-host-actions";
import { BookingDetailPrice } from "./booking-detail-price";
import { BookingOperationsTimeline } from "./booking-operations-timeline";

function GuestInfo({ user }: { user: UserModel | null }) {
    const t = useTranslations();

    if (!user) return null;

    return (
        <div className="flex flex-col gap-3">
            <h2 className="text-xl font-semibold">{t("features.bookings.detail.guestSection")}</h2>
            <div className="flex items-center gap-3">
                <UserAvatar user={user} className="size-12" />
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{user.getFullName()}</span>
                    <span className="text-sm text-muted-foreground">{user.email}</span>
                </div>
            </div>
        </div>
    );
}

export function BookingDetailTabDetails({
    booking,
    petMap,
    isHost = false,
}: {
    booking: BookingModel;
    petMap: Map<string, PetModel>;
    isHost?: boolean;
}) {
    const t = useTranslations();
    const { routes } = useNavigation();
    const { openWithBooking, isPending: isContactPending } = useOpenConversation();
    const clientCancel = useClientCancelDialog(booking);

    const canModifyOrCancel = booking.isPending() || booking.isConfirmed();

    return (
        <div className="flex flex-col gap-6 p-4 pb-10">
            {isHost && <GuestInfo user={booking.user} />}

            {isHost && <BookingDetailHostActions booking={booking} />}

            {booking.pets && booking.pets.length > 0 && (
                <div className="flex flex-col gap-2">
                    <h2 className="text-xl font-semibold">{t("features.bookings.detail.pets")}</h2>
                    <div className="flex flex-col">
                        {booking.pets.map((pet) => {
                            const fullPet = petMap.get(pet.id);
                            return (
                                <Link
                                    key={pet.id}
                                    href={routes.PetDetails({ id: pet.id })}
                                    className="flex items-center justify-between py-2 hover:opacity-75 transition-opacity"
                                >
                                    <div className="flex items-center gap-3">
                                        <ShapeMedia
                                            imageUrl={fullPet?.getAvatarUrl() ?? null}
                                            shapeClassName="size-10"
                                        />
                                        <div className="flex flex-col gap-0.5">
                                            <div className="flex items-center gap-1.5">
                                                {fullPet?.animalType && (
                                                    <PetTypeIllustration
                                                        code={fullPet.animalType.code}
                                                        name={fullPet.animalType.name}
                                                        className="size-4"
                                                    />
                                                )}
                                                <span className="text-sm font-medium">
                                                    {pet.name}
                                                </span>
                                            </div>
                                            {fullPet?.animalType && (
                                                <span className="text-xs text-muted-foreground">
                                                    {fullPet.animalType.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        {pet.subtotal} €
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                </div>
            )}

            {booking.services && booking.services.length > 0 && (
                <div className="flex flex-col gap-2">
                    <h2 className="text-xl font-semibold">
                        {t("features.bookings.detail.services")}
                    </h2>
                    <div className="flex flex-col gap-1">
                        {booking.services.map((svc) => (
                            <div key={svc.id} className="flex items-center justify-between py-1">
                                <div className="flex items-center gap-2">
                                    <Star className="size-4 text-muted-foreground" />
                                    <span className="text-sm">
                                        {svc.name} × {svc.quantity}
                                    </span>
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {svc.subtotal} €
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <BookingDetailPrice booking={booking} isHost={isHost} />

            {booking.specialRequests && (
                <div className="flex flex-col gap-2">
                    <h2 className="text-xl font-semibold">
                        {t("features.bookings.detail.specialRequests")}
                    </h2>
                    <p className="text-sm text-muted-foreground">{booking.specialRequests}</p>
                </div>
            )}

            {isHost && (
                <BookingOperationsTimeline activityId={booking.activityId} bookingId={booking.id} />
            )}

            <div className="flex flex-col">
                <NavRow
                    icon={CardIcon}
                    label={t("features.bookings.detail.viewInvoice")}
                    displayArrow
                    comingSoon
                />
                <NavRow
                    icon={ChatRound}
                    label={
                        isHost
                            ? t("features.bookings.detail.messageGuest")
                            : t("features.bookings.detail.messageHost")
                    }
                    displayArrow
                    disabled={isContactPending}
                    onClick={() => openWithBooking(booking.id)}
                />
                {!isHost && canModifyOrCancel && (
                    <>
                        <NavRow
                            icon={PenNewSquare}
                            label={t("features.bookings.detail.modifyBooking")}
                            displayArrow
                            comingSoon
                        />
                        <NavRow
                            icon={CloseCircle}
                            label={t("features.bookings.detail.cancelBooking")}
                            destructive
                            disabled={clientCancel.isLoading}
                            onClick={clientCancel.open}
                        />
                    </>
                )}
            </div>

            {clientCancel.dialog}
        </div>
    );
}
