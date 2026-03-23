import { DEFAULT_LOCALE, LOCALES } from "@/dictionaries";
import { defineRouting } from "next-intl/routing";

export const routing = defineRouting({
    locales: LOCALES,
    defaultLocale: DEFAULT_LOCALE,
    localePrefix: "as-needed",
    domains: [
        {
            domain: "www.kennelo.com",
            defaultLocale: "en",
            locales: ["en", "ar"],
        },
        {
            domain: "www.kennelo.fr",
            defaultLocale: "fr",
            locales: ["fr"],
        },
    ],
    localeDetection: true,
    localeCookie: {
        name: "USER_LOCALE",
        maxAge: 60 * 60 * 24 * 365,
    },
});
