"use client";

import { useTranslations } from "next-intl";
import { ChangePasswordForm } from "@/features/profile";

export default function MyProfileChangePassword() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            <section className="grid gap-4">
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
