"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { FileText } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";

const DESCRIPTION_MAX = 220;
const SECONDARY_BUTTON_CLASS =
    "h-10 w-full rounded-md bg-zinc-100 text-sm font-medium text-zinc-900 hover:bg-zinc-200";

type HostAboutSectionProps = {
    description: string | null;
};

export function HostAboutSection({ description }: HostAboutSectionProps) {
    const t = useTranslations();
    const [expanded, setExpanded] = useState(false);

    if (!description) {
        return (
            <section className="flex flex-col gap-3 px-1 pt-4">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.about")}
                </h2>
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <FileText />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.aboutEmpty")}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            </section>
        );
    }

    const shouldTruncate = description.length > DESCRIPTION_MAX;
    const truncated = shouldTruncate ? `${description.slice(0, DESCRIPTION_MAX)}...` : description;
    const textToDisplay = expanded ? description : truncated;

    return (
        <section className="flex flex-col gap-3 px-1 pt-4">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.about")}
            </h2>
            <div className="flex flex-col items-center gap-4">
                <p className="whitespace-pre-wrap text-sm text-foreground">{textToDisplay}</p>
                {shouldTruncate && (
                    <Button
                        variant="secondary"
                        className={SECONDARY_BUTTON_CLASS}
                        onClick={() => setExpanded((value) => !value)}
                    >
                        {expanded
                            ? t("features.host.detail.readLess")
                            : t("features.host.detail.readMore")}
                    </Button>
                )}
            </div>
        </section>
    );
}
