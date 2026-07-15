"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import posthog from "posthog-js";
import { Button } from "@workspace/ui/components/button";
import { useNavigation } from "@/hooks/use-navigation";

const STORAGE_KEY = "cookie_consent";

type CookieConsent = "all" | "essential";

export function CookieConsentBanner() {
    const t = useTranslations();
    const { routes } = useNavigation();
    const [consent, setConsent] = useState<CookieConsent | null>("all");

    useEffect(() => {
        const stored = window.localStorage.getItem(STORAGE_KEY) as CookieConsent | null;
        setConsent(stored);
    }, []);

    const choose = (value: CookieConsent) => {
        window.localStorage.setItem(STORAGE_KEY, value);
        setConsent(value);
        if (value === "all") {
            posthog.opt_in_capturing();
        } else {
            posthog.opt_out_capturing();
        }
    };

    if (consent !== null) {
        return null;
    }

    return (
        <div
            role="region"
            aria-label={t("features.legal.cookieConsent.message")}
            className="fixed inset-x-0 bottom-0 z-50 border-t bg-card p-4 shadow-lg"
        >
            <div className="container mx-auto flex max-w-3xl flex-col items-center gap-3 sm:flex-row sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    {t("features.legal.cookieConsent.message")}{" "}
                    <Link href={routes.Confidentialite()} className="text-primary hover:underline">
                        {t("features.legal.cookieConsent.learnMore")}
                    </Link>
                </p>
                <div className="flex shrink-0 gap-2">
                    <Button variant="flat" size="sm" onClick={() => choose("essential")}>
                        {t("features.legal.cookieConsent.rejectNonEssential")}
                    </Button>
                    <Button size="sm" onClick={() => choose("all")}>
                        {t("features.legal.cookieConsent.acceptAll")}
                    </Button>
                </div>
            </div>
        </div>
    );
}
