import { routing } from "@/lib/i18n/routing";
import { NextRequest, NextResponse } from "next/server";
import { Middleware } from ".";

const SYSTEM_SUBDOMAINS = new Set(["www", "preprod", "staging", "admin", "api", "ws", "cdn"]);

function extractSubdomain(request: NextRequest): string | null {
    const hostname = (request.headers.get("host") ?? "").split(":")[0]!;
    const segments = hostname.split(".");
    const first = segments[0]!;
    const second = segments[1];

    if (SYSTEM_SUBDOMAINS.has(first)) return null;

    const isLocalhost = segments[segments.length - 1] === "localhost";
    const isSubdomain =
        first !== "www" && (isLocalhost ? segments.length > 1 : segments.length > 2);

    if (isSubdomain) {
        return first;
    }

    const isWwwPrefixedSubdomain =
        first === "www" &&
        second !== undefined &&
        (isLocalhost ? segments.length > 2 : segments.length > 3);

    return isWwwPrefixedSubdomain ? second : null;
}

function resolveLocale(
    request: NextRequest,
    pathname: string,
): { locale: string; cleanPathname: string } {
    const locales = routing.locales as readonly string[];
    const segments = pathname.split("/");

    if (segments[1] && locales.includes(segments[1])) {
        return {
            locale: segments[1]!,
            cleanPathname: `/${segments.slice(2).join("/")}`,
        };
    }

    const cookieLocale = request.cookies.get("USER_LOCALE")?.value;
    if (cookieLocale && locales.includes(cookieLocale)) {
        return { locale: cookieLocale, cleanPathname: pathname };
    }

    return { locale: routing.defaultLocale, cleanPathname: pathname };
}

export class SubdomainMiddleware implements Middleware {
    handle(request: NextRequest): NextResponse | null {
        const { pathname } = request.nextUrl;
        const subdomain = extractSubdomain(request);

        if (!subdomain) return null;

        const { locale, cleanPathname } = resolveLocale(request, pathname);

        if (cleanPathname.startsWith("/admin")) {
            return NextResponse.redirect(new URL("/", request.url));
        }

        const targetPath =
            cleanPathname === "/"
                ? `/${locale}/s/${subdomain}`
                : `/${locale}/s/${subdomain}${cleanPathname}`;

        const targetUrl = request.nextUrl.clone();
        targetUrl.pathname = targetPath;

        return NextResponse.rewrite(targetUrl);
    }
}
