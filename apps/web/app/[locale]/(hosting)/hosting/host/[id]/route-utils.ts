const ACTIVITY_ROUTE_MARKER = "/hosting/host/";

export function subRouteOf(path: string): string {
    const clean = path.split("?")[0] ?? "";
    const index = clean.indexOf(ACTIVITY_ROUTE_MARKER);
    if (index === -1) {
        return "";
    }
    const rest = clean.slice(index + ACTIVITY_ROUTE_MARKER.length);
    const slash = rest.indexOf("/");
    return slash === -1 ? "" : rest.slice(slash);
}

export function isActiveSubRoute(pathname: string, href: string): boolean {
    const current = subRouteOf(pathname);
    const target = subRouteOf(href);
    return target !== "" && (current === target || current.startsWith(`${target}/`));
}
