"use client";

import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";

type HostNotFoundProps = {
    onBack: () => void;
};

export function HostNotFound({ onBack }: HostNotFoundProps) {
    const t = useTranslations();
    return (
        <div className="flex flex-col items-center justify-center gap-3 p-8 text-center">
            <h1 className="text-lg font-semibold">{t("features.host.detail.notFound")}</h1>
            <p className="text-sm text-muted-foreground">
                {t("features.host.detail.notFoundDescription")}
            </p>
            <Button onClick={onBack} variant="outline">
                {t("features.host.detail.back")}
            </Button>
        </div>
    );
}
