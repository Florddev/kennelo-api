"use client";

import { useTranslations } from "next-intl";
import { ChangePasswordForm } from "@/features/profile";

export default function MyProfileChangePassword() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            <section className="grid grid-cols-1 md:grid-cols-[1fr_3fr] gap-4 md:gap-8">
                <div>
                    <h2 className="hidden md:block text-lg font-semibold">
                        {t("features.auth.changePassword")}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t("features.auth.changePasswordDescription")}
                    </p>
                </div>

                <ChangePasswordForm />
            </section>
        </div>
    );
}
