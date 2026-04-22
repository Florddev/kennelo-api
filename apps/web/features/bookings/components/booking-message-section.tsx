"use client";

import { useTranslations } from "next-intl";
import { Textarea } from "@workspace/ui/components/textarea";

type BookingMessageSectionProps = {
    value: string;
    onChange: (value: string) => void;
};

export function BookingMessageSection({ value, onChange }: BookingMessageSectionProps) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <div className="flex items-baseline gap-2">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.bookings.checkout.messageSection")}
                </h2>
                <span className="text-xs text-muted-foreground">
                    {t("features.bookings.checkout.messageOptional")}
                </span>
            </div>
            <Textarea
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={t("features.bookings.checkout.messagePlaceholder")}
                rows={5}
                maxLength={1000}
                className="resize-none rounded-2xl"
            />
        </section>
    );
}
