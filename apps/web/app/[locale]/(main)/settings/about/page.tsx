"use client";

import { useAuth } from "@/features/auth";
import { Separator } from "@workspace/ui/components/separator";
import { CalendarDays } from "lucide-react";
import { useTranslations } from "next-intl";
import {
    UploadAvatarForm,
    UpdateProfileForm,
    ChangeEmailForm,
    DeleteAccountDialog,
} from "@/features/profile";
import { Loaded } from "@workspace/ui/components/loaded";

export default function MyProfileAbout() {
    const { user, isLoaded } = useAuth();
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-8 pb-8">
            <section className="rounded-xl border bg-muted/30 p-6 flex items-center gap-8">
                <UploadAvatarForm />

                <div className="flex flex-col gap-1 min-w-0">
                    <div className="text-xl font-semibold">
                        <Loaded isLoaded={isLoaded} className="w-48">
                            {user?.getFullName()}
                        </Loaded>
                    </div>
                    <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        <CalendarDays className="size-3.5 shrink-0" />
                        <Loaded isLoaded={isLoaded} className="w-48">
                            {t("features.auth.memberSince", { date: user?.createdAt || "" })}
                        </Loaded>
                    </span>
                </div>
            </section>

            <Separator />

            <section className="grid grid-cols-[1fr_3fr] gap-8">
                <div>
                    <h2 className="text-lg font-semibold">
                        {t("features.auth.personalInformation")}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t("features.auth.personalInfoDescription")}
                    </p>
                </div>
                <UpdateProfileForm />
            </section>

            <Separator />

            <section className="grid grid-cols-[1fr_3fr] gap-8">
                <div>
                    <h2 className="text-lg font-semibold">{t("features.auth.accountEmail")}</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t("features.auth.accountEmailDescription")}
                    </p>
                </div>
                <ChangeEmailForm />
            </section>

            <Separator />

            <section className="grid grid-cols-[1fr_3fr] gap-8">
                <div>
                    <h2 className="text-lg font-semibold text-destructive">
                        {t("features.auth.dangerZone")}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t("features.auth.dangerZoneDescription")}
                    </p>
                </div>

                <div className="space-y-4">
                    <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 space-y-3">
                        <div>
                            <p className="text-sm font-medium">
                                {t("features.auth.deleteAccount")}
                            </p>
                            <p className="text-sm text-muted-foreground mt-0.5">
                                {t("features.auth.dangerZoneConfirmation")}
                            </p>
                        </div>
                        <DeleteAccountDialog />
                    </div>
                </div>
            </section>
        </div>
    );
}
