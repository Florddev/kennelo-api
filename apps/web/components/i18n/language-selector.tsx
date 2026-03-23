"use client";

import { useLocale, useTranslations } from "next-intl";
import { usePathname, useRouter } from "next/navigation";
import { useTransition } from "react";
import { CheckIcon } from "lucide-react";
import { DropdownMenuItem, DropdownMenuLabel } from "@workspace/ui/components/dropdown-menu";
import { LOCALES, LOCALE_INFO, type Locale } from "@/dictionaries";
import { changeLocale } from "@workspace/modules/users";
import { useAuth } from "@/features/auth";

interface LanguageSelectorProps {
    onLocaleChange?: (locale: Locale) => void;
}

export function useLanguageSelector() {
    const [isPending, startTransition] = useTransition();
    const { isAuthenticated } = useAuth();
    const locale = useLocale() as Locale;
    const router = useRouter();
    const pathname = usePathname();

    const handleLocaleChange = async (newLocale: Locale) => {
        if (newLocale === locale) return;
        if (isAuthenticated) {
            await changeLocale(newLocale).catch(() => undefined);
        }

        startTransition(() => {
            const pathWithoutLocale = pathname.replace(`/${locale}`, "");
            const newPath = `/${newLocale}${pathWithoutLocale || ""}`;
            router.replace(newPath);
        });
    };

    return { locale, isPending, handleLocaleChange };
}

export function LanguageSelectorItems({ onLocaleChange }: LanguageSelectorProps) {
    const t = useTranslations();
    const { locale, isPending, handleLocaleChange } = useLanguageSelector();

    const handleClick = async (loc: Locale) => {
        await handleLocaleChange(loc);
        onLocaleChange?.(loc);
    };

    return (
        <>
            <DropdownMenuLabel>{t("ui.navigation.language")}</DropdownMenuLabel>
            {LOCALES.map((loc) => {
                const isActive = loc === locale;
                const { flag, name } = LOCALE_INFO[loc];
                const translatedName = t(`settings.language.${loc}`);

                return (
                    <DropdownMenuItem
                        key={loc}
                        onClick={() => handleClick(loc)}
                        disabled={isPending}
                        className="gap-3"
                    >
                        <span className="text-base">{flag}</span>
                        <span className="flex-1">
                            <span className="block text-sm font-medium">{name}</span>
                            <span className="text-muted-foreground text-xs block">
                                {translatedName}
                            </span>
                        </span>
                        {isActive && <CheckIcon className="h-4 w-4 shrink-0" />}
                    </DropdownMenuItem>
                );
            })}
        </>
    );
}
