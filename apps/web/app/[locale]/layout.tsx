import { hasLocale, NextIntlClientProvider } from "next-intl";
import { routing } from "@/lib/i18n/routing";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { DirectionProvider } from "@workspace/ui/components/direction";
import { LocaleDirection } from "@/dictionaries";
import LocaleUpdater from "@/components/i18n/locale-updater";
import { JSX } from "react";

export function generateStaticParams() {
    return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: { locale: string } }) {
    const { locale } = await params;
    const t = await getTranslations({ locale });

    return {
        title: "Kennelo",
        description: t("app.description"),
    };
}

export default async function LocaleLayout({
    children,
    params,
}: {
    children: React.ReactNode;
    params: Promise<{ locale: string }>;
}): Promise<JSX.Element> {
    const { locale } = await params;
    if (!hasLocale(routing.locales, locale)) {
        notFound();
    }

    setRequestLocale(locale);
    const t = await getTranslations({ locale });
    const dir = t("settings.dir") as LocaleDirection;

    return (
        <DirectionProvider direction={dir} dir={dir}>
            <LocaleUpdater locale={locale} direction={dir} />
            <NextIntlClientProvider locale={locale}>{children}</NextIntlClientProvider>
        </DirectionProvider>
    );
}
