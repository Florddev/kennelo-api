import createMiddleware from "next-intl/middleware";
import { NextRequest } from "next/server";

import { routing } from "@/lib/i18n/routing";
import { Middleware } from ".";

export class I18nMiddleware implements Middleware {
    handle(request: NextRequest) {
        return createMiddleware(routing)(request);
    }
}
