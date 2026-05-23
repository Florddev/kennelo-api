import { MetadataRoute } from "next";
import { routing } from "@/lib/i18n/routing";
import { headers } from "next/headers";

export const dynamic = "force-static";

interface RouteConfig {
    href: string;
    changeFrequency?: "always" | "hourly" | "daily" | "weekly" | "monthly" | "yearly" | "never";
    priority?: number;
}

const routes: RouteConfig[] = [
    {
        href: "/",
        changeFrequency: "daily",
        priority: 1,
    },
    {
        href: "/bookings",
        changeFrequency: "weekly",
        priority: 0.8,
    },
    {
        href: "/pets",
        changeFrequency: "weekly",
        priority: 0.8,
    },
    {
        href: "/messages",
        changeFrequency: "weekly",
        priority: 0.7,
    },
];

function getCurrentDomain(host: string) {
    return routing.domains?.find(
        (domain) => domain.domain === host || domain.domain === `www.${host}`,
    );
}

function getLocaleDomainMap() {
    const localeDomainMap = new Map<string, string>();
    for (const domain of routing.domains || []) {
        for (const locale of domain.locales) {
            localeDomainMap.set(locale, domain.domain);
        }
    }
    return localeDomainMap;
}

function getLocalizedPathname(locale: string, href: string) {
    const domain = routing.domains?.find((item) => item.locales.some((value) => value === locale));
    if (!domain) {
        return href;
    }
    if (domain.defaultLocale === locale) {
        return href;
    }
    if (href === "/") {
        return `/${locale}`;
    }
    return `/${locale}${href}`;
}

function createAlternateLanguages(
    routeHref: string,
    locale: string,
    localeDomainMap: Map<string, string>,
) {
    const languages: Record<string, string> = {};
    const alternateLocales = Array.from(localeDomainMap.entries());

    for (const [alternateLocale, alternateDomain] of alternateLocales) {
        const pathname = getLocalizedPathname(alternateLocale, routeHref);
        languages[alternateLocale] = new URL(pathname, `https://${alternateDomain}`).toString();
    }

    const defaultLocaleDomain = localeDomainMap.get(routing.defaultLocale);
    if (defaultLocaleDomain) {
        const defaultPathname = getLocalizedPathname(routing.defaultLocale, routeHref);
        languages["x-default"] = new URL(
            defaultPathname,
            `https://${defaultLocaleDomain}`,
        ).toString();
    }

    return languages;
}

function createSitemapEntry(
    route: RouteConfig,
    locale: string,
    currentDomain: { domain: string },
    localeDomainMap: Map<string, string>,
): MetadataRoute.Sitemap[number] {
    const pathname = getLocalizedPathname(locale, route.href);
    const url = new URL(pathname, `https://${currentDomain.domain}`).toString();
    const languages = createAlternateLanguages(route.href, locale, localeDomainMap);

    return {
        url,
        lastModified: new Date().toISOString().split("T")[0],
        changeFrequency: route.changeFrequency,
        priority: route.priority,
        ...(Object.keys(languages).length > 0 && {
            alternates: {
                languages,
            },
        }),
    };
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
    if (process.env.NEXT_PUBLIC_ROUTE_MODE === "static") return [];

    const headersList = await headers();
    const host = headersList.get("host") || "";

    const currentDomain = getCurrentDomain(host);

    if (!currentDomain) {
        return [];
    }

    const localeDomainMap = getLocaleDomainMap();

    const entries: MetadataRoute.Sitemap = [];

    for (const route of routes) {
        for (const locale of currentDomain.locales) {
            entries.push(createSitemapEntry(route, locale, currentDomain, localeDomainMap));
        }
    }

    return entries;
}
