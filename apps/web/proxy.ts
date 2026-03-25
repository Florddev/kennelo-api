import { NextRequest } from "next/server";

import { AuthGuardMiddleware } from "./lib/middlewares/auth-guard.middleware";
import { I18nMiddleware } from "./lib/middlewares/i18n.middleware";
import { SubdomainMiddleware } from "./lib/middlewares/subdomain.middleware";
import { Middleware, runMiddlewares } from "./lib/middlewares";

const middlewares: Middleware[] = [
    new SubdomainMiddleware(),
    new AuthGuardMiddleware({
        guestOnly: ["/s/accounts/login", "/s/accounts/register"],
        authRequired: ["/s/my/*", "/become-host"],
        roleRequired: [{ roles: ["admin"], patterns: ["/s/admin/*"] }],
    }),
    new I18nMiddleware(),
];

export default async function proxy(request: NextRequest) {
    return runMiddlewares(request, middlewares);
}

export const config = {
    matcher: "/((?!api|trpc|_next|_vercel|.*\\..*).*)",
};
