import { hasLocale } from "next-intl";
import createMiddleware from "next-intl/middleware";
import { NextRequest } from "next/server";

import { routing } from "@/lib/i18n/routing";
import { Middleware } from ".";

export class I18nMiddleware implements Middleware {
    handle(request: NextRequest) {
        const host = request.headers.get("host")?.split(":")[0] ?? "";
        const domainConfig = routing.domains?.find((d) => d.domain === host) as
            | { domain: string; defaultLocale: string; locales: readonly string[] }
            | undefined;

        if (domainConfig) {
            return createMiddleware(routing)(request);
        }

        const acceptLanguage = request.headers.get("accept-language");
        const browserLocale = acceptLanguage?.split(",")[0]?.split("-")[0]?.trim() ?? "en";

        const defaultLocale = hasLocale(routing.locales, browserLocale)
            ? browserLocale
            : routing.defaultLocale;

        return createMiddleware({ ...routing, defaultLocale })(request);
    }
}
