"use client";

import { useTranslations } from "next-intl";
import {
    Phone,
    Letter,
    UserCircle,
    HeadphonesRound,
    QuestionCircle,
    DangerCircle,
} from "@solar-icons/react";

import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";

import type { UserModel } from "@workspace/modules/users";

import { NavRow } from "@/components/navigation/nav-row";

export function BookingDetailTabSupport({
    manager,
    activityName,
    phone,
    email,
}: {
    manager: UserModel | null;
    activityName?: string | null;
    phone?: string | null;
    email?: string | null;
}) {
    const t = useTranslations();

    const handleCall = () => {
        if (phone) window.open(`tel:${phone}`, "_self");
    };

    const handleEmail = () => {
        if (email) window.open(`mailto:${email}`, "_self");
    };

    return (
        <div className="flex flex-col gap-6 p-4 pb-10">
            <div className="flex flex-col gap-3">
                <h2 className="text-xl font-semibold">
                    {t("features.bookings.detail.hostSection")}
                </h2>

                {manager ? (
                    <>
                        <div className="flex items-center gap-3">
                            <Avatar className="size-12">
                                <AvatarImage
                                    src={manager.avatarUrl ?? undefined}
                                    alt={manager.getFullName()}
                                />
                                <AvatarFallback>{manager.getInitials()}</AvatarFallback>
                            </Avatar>
                            <div className="flex flex-col gap-0.5">
                                <span className="font-semibold">{manager.getFullName()}</span>
                                {activityName && (
                                    <span className="text-sm text-muted-foreground">
                                        {activityName}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col">
                            {phone && (
                                <NavRow
                                    icon={Phone}
                                    label={t("features.bookings.detail.callHost")}
                                    onClick={handleCall}
                                    displayArrow
                                />
                            )}
                            {email && (
                                <NavRow
                                    icon={Letter}
                                    label={t("features.bookings.detail.emailHost")}
                                    onClick={handleEmail}
                                    displayArrow
                                />
                            )}
                            <NavRow
                                icon={UserCircle}
                                label={t("features.bookings.detail.viewProfile")}
                                displayArrow
                                comingSoon
                            />
                        </div>
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {t("features.bookings.detail.noHost")}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-3">
                <h2 className="text-xl font-semibold">
                    {t("features.bookings.detail.supportSection")}
                </h2>
                <div className="flex flex-col">
                    <NavRow
                        icon={HeadphonesRound}
                        label={t("features.bookings.detail.kenneloAssistance")}
                        displayArrow
                        comingSoon
                    />
                    <NavRow
                        icon={QuestionCircle}
                        label={t("features.bookings.detail.helpCenter")}
                        displayArrow
                        comingSoon
                    />
                    <NavRow
                        icon={DangerCircle}
                        label={t("features.bookings.detail.reportIssue")}
                        displayArrow
                        comingSoon
                    />
                </div>
            </div>
        </div>
    );
}
