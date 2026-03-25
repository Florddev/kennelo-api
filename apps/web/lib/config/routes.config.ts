export const routesConfig = {
    mode: (process.env.NEXT_PUBLIC_ROUTE_MODE as "dynamic" | "static") || "dynamic",
    preservedParams: ["locale", "lang"],
} as const;

type SearchParamsValue = string | number | boolean;

type RouteParams = {
    [key: string]: string | number | Record<string, SearchParamsValue> | undefined;
    search_params?: Record<string, SearchParamsValue>;
};

export function buildRoute(path: string, params?: RouteParams): string {
    if (!params || Object.keys(params).length === 0) {
        params = {};
    }

    if (path.includes("[locale]") && !params.locale) {
        path = path.replace("[locale]/", "");
    }

    const mode = routesConfig.mode;
    const preserved = routesConfig.preservedParams;
    const queryParams: Record<string, string> = {};
    let finalPath = path;

    Object.entries(params).forEach(([key, value]) => {
        if (key === "search_params") {
            return;
        }

        if (mode === "static" && !(preserved as readonly string[]).includes(key)) {
            queryParams[key] = String(value);
        } else {
            finalPath = finalPath.replace(`[${key}]`, String(value));
        }
    });

    const searchParams = params.search_params;
    if (searchParams) {
        Object.entries(searchParams).forEach(([key, value]) => {
            queryParams[key] = String(value);
        });
    }

    const query = new URLSearchParams(queryParams).toString();
    return query ? `${finalPath}?${query}` : finalPath;
}
