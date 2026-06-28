"use client";

import Link from "next/link";
import { ResetPasswordForm } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";

export default function ResetPasswordPage() {
    const { routes, router, params } = useNavigation<{ token?: string; email?: string }>();
    const t = useTranslations();

    const token = params.token ?? "";
    const email = params.email ?? "";

    const handleSuccess = () => {
        router.push(routes.Login());
    };

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-12">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-3xl font-bold">
                                {t("features.auth.resetPassword.title")}
                            </h1>
                            <p className="text-muted-foreground">
                                {t("features.auth.resetPassword.description")}
                            </p>
                        </div>
                        {token && email ? (
                            <ResetPasswordForm
                                token={token}
                                email={email}
                                onSuccess={handleSuccess}
                            />
                        ) : (
                            <p className="text-center text-sm text-destructive">
                                {t("features.auth.resetPassword.invalidLink")}
                            </p>
                        )}
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
