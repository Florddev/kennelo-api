"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { verifyEmail, resendVerification } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { useNavigation } from "@/hooks/use-navigation";
import { logger } from "@/lib/logger";

type VerificationStatus = "verifying" | "success" | "error";

export default function VerifyEmailPage() {
    const { routes, params } = useNavigation<{
        id?: string;
        hash?: string;
        expires?: string;
        signature?: string;
    }>();
    const t = useTranslations();
    const { isAuthenticated } = useAuth();
    const { isLoading, execute } = useAsyncState();
    const [status, setStatus] = useState<VerificationStatus>("verifying");
    const [resent, setResent] = useState(false);

    const { id, hash, expires, signature } = params;

    useEffect(() => {
        if (!id || !hash || !expires || !signature) {
            setStatus("error");
            return;
        }

        verifyEmail({ id, hash, expires, signature })
            .then(() => setStatus("success"))
            .catch((error: unknown) => {
                logger.error("Email verification failed:", error);
                setStatus("error");
            });
    }, [id, hash, expires, signature]);

    const handleResend = async () => {
        await execute(() => resendVerification(), {
            onSuccess: () => setResent(true),
        });
    };

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-8">
                        <div className="flex flex-col items-center gap-3 text-center">
                            <h1 className="text-3xl font-bold">
                                {t("features.auth.verifyEmail.title")}
                            </h1>
                            <p className="text-muted-foreground">
                                {t(`features.auth.verifyEmail.${status}`)}
                            </p>
                        </div>
                        {status === "error" && isAuthenticated && !resent && (
                            <Button
                                type="button"
                                size="lg"
                                className="w-full font-medium text-md"
                                disabled={isLoading}
                                onClick={handleResend}
                            >
                                {t("features.auth.verifyEmail.resend")}
                            </Button>
                        )}
                        {resent && (
                            <p className="text-center text-sm text-muted-foreground">
                                {t("features.auth.verifyEmail.resent")}
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
