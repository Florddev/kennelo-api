export const normalizePath = (path: string, locale: string) => {
    const withoutQuery = path.split("?")[0] ?? "";
    const cleanPath = withoutQuery.split("#")[0] || "/";
    const localePrefix = `/${locale}`;
    const hasLocalePrefix = cleanPath === localePrefix || cleanPath.startsWith(`${localePrefix}/`);
    const noLocalePath = hasLocalePrefix ? cleanPath.slice(localePrefix.length) || "/" : cleanPath;

    let noTrailingSlash = noLocalePath;
    while (noTrailingSlash.length > 1 && noTrailingSlash.endsWith("/")) {
        noTrailingSlash = noTrailingSlash.slice(0, -1);
    }

    return noTrailingSlash;
};

export const isActivePath = (href: string, pathname: string, locale: string) => {
    const currentPath = normalizePath(pathname, locale);
    const targetPath = normalizePath(href, locale);

    if (targetPath === "/") {
        return currentPath === "/";
    }

    return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
};
