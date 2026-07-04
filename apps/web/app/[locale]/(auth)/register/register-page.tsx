"use client";

import Link from "next/link";
import { RegisterForm, GoogleSignInButton } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";
import { safeRedirectPath } from "@/lib/safe-redirect";

export default function RegisterPage() {
    const { routes, router, params } = useNavigation<{ redirect_url?: string }>();
    const t = useTranslations();

    const handleSuccess = () => {
        const target = safeRedirectPath(params.redirect_url);
        router.push(target ?? routes.Home());
    };

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm md:max-w-md">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-12">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-3xl font-bold">
                                {t("features.auth.create-account")}
                            </h1>
                            <p className="text-muted-foreground">
                                {t("features.auth.register.description")}
                            </p>
                        </div>
                        <div className="flex flex-col gap-6">
                            <RegisterForm onSuccess={handleSuccess} />
                            <GoogleSignInButton onSuccess={() => handleSuccess()} />
                        </div>
                    </FieldGroup>
                    <FieldDescription className="px-6 text-center">
                        {t("features.auth.alreadyHaveAccount")}{" "}
                        <Link
                            href={routes.Login(
                                params?.redirect_url
                                    ? { search_params: { redirect_url: params.redirect_url } }
                                    : {},
                            )}
                            className="text-primary hover:underline"
                        >
                            {t("features.auth.login.here")}
                        </Link>
                    </FieldDescription>
                </div>
            </div>
        </div>
    );
}
