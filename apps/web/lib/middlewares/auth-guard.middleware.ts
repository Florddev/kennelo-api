import { NextRequest, NextResponse } from "next/server";
import { jwtHelper } from "@workspace/common";

import { routing } from "@/lib/i18n/routing";
import { Middleware } from ".";
import { routes } from "../routes";

interface RoleRule {
    roles: string[];
    patterns: string[];
}

export interface AuthGuardConfig {
    guestOnly?: string[];
    authRequired?: string[];
    roleRequired?: RoleRule[];
    redirects?: {
        ifAuthenticated?: string;
        ifUnauthenticated?: string;
        ifForbidden?: string;
    };
}

function matchesPattern(pathname: string, pattern: string): boolean {
    const normalize = (p: string) => (p.startsWith("/") ? p : `/${p}`);
    const regexStr = normalize(pattern)
        .replace(/[.+?^${}()|[\]\\]/g, "\\$&")
        .replace(/\\\[([^\]]+)\\\]/g, "[^/]+")
        .replace(/\*/g, ".*");

    return new RegExp(`^${regexStr}$`).test(normalize(pathname));
}

function stripLocale(pathname: string): string {
    const locales = routing.locales as readonly string[];
    const segments = pathname.split("/");
    if (segments[1] && locales.includes(segments[1])) {
        return `/${segments.slice(2).join("/")}`;
    }
    return pathname;
}

function buildRedirectUrl(request: NextRequest, targetPath: string): URL {
    const pathname = request.nextUrl.pathname;
    const locales = routing.locales as readonly string[];
    const defaultLocale = routing.defaultLocale;
    const firstSegment = pathname.split("/")[1];

    const hasNonDefaultLocale =
        firstSegment && locales.includes(firstSegment) && firstSegment !== defaultLocale;

    const normalized = targetPath.startsWith("/") ? targetPath : `/${targetPath}`;
    const redirectPath = hasNonDefaultLocale ? `/${firstSegment}${normalized}` : normalized;

    return new URL(redirectPath, request.url);
}

function checkRoleAccess(
    request: NextRequest,
    pathname: string,
    token: string | null,
    isAuthenticated: boolean,
    rules: RoleRule[],
    ifUnauthenticated: string,
    ifForbidden: string,
): NextResponse | null {
    for (const rule of rules) {
        if (!rule.patterns.some((p) => matchesPattern(pathname, p))) continue;

        if (!isAuthenticated) {
            return NextResponse.redirect(buildRedirectUrl(request, ifUnauthenticated));
        }

        const userRoles = jwtHelper.getProperty<string[]>(token!, "roles") ?? [];
        if (!rule.roles.some((r) => userRoles.includes(r))) {
            return NextResponse.redirect(buildRedirectUrl(request, ifForbidden));
        }
    }
    return null;
}

export class AuthGuardMiddleware implements Middleware {
    private readonly ifAuthenticated: string;
    private readonly ifUnauthenticated: string;
    private readonly ifForbidden: string;

    constructor(private readonly config: AuthGuardConfig) {
        this.ifAuthenticated = config.redirects?.ifAuthenticated ?? "/";
        this.ifUnauthenticated = config.redirects?.ifUnauthenticated ?? routes.Login();
        this.ifForbidden = config.redirects?.ifForbidden ?? "/";
    }

    private matchesAny(pathname: string, patterns?: string[]): boolean {
        return patterns?.some((p) => matchesPattern(pathname, p)) ?? false;
    }

    handle(request: NextRequest): NextResponse | null {
        const pathname = stripLocale(request.nextUrl.pathname);
        const token = request.cookies.get("access_token")?.value ?? null;
        const isAuthenticated = token !== null && !jwtHelper.isExpired(token);

        if (this.matchesAny(pathname, this.config.guestOnly) && isAuthenticated) {
            return NextResponse.redirect(buildRedirectUrl(request, this.ifAuthenticated));
        }

        if (this.matchesAny(pathname, this.config.authRequired) && !isAuthenticated) {
            return NextResponse.redirect(buildRedirectUrl(request, this.ifUnauthenticated));
        }

        if (this.config.roleRequired?.length) {
            return checkRoleAccess(
                request,
                pathname,
                token,
                isAuthenticated,
                this.config.roleRequired,
                this.ifUnauthenticated,
                this.ifForbidden,
            );
        }

        return null;
    }
}
