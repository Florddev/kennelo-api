"use client";

import { useState } from "react";
import Link from "next/link";
import { ForgotPasswordForm } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";

export default function ForgotPasswordPage() {
    const { routes } = useNavigation();
    const t = useTranslations();
    const [sent, setSent] = useState(false);

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-12">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-3xl font-bold">
                                {t("features.auth.forgotPassword.title")}
                            </h1>
                            <p className="text-muted-foreground">
                                {sent
                                    ? t("features.auth.forgotPassword.sent")
                                    : t("features.auth.forgotPassword.description")}
                            </p>
                        </div>
                        {!sent && <ForgotPasswordForm onSuccess={() => setSent(true)} />}
                    </FieldGroup>
                    <FieldDescription className="px-6 text-center">
                        <Link href={routes.Login()} className="text-primary hover:underline">
                            {t("features.auth.backToLogin")}
                        </Link>
                    </FieldDescription>
                </div>
            </div>
        </div>
    );
}
