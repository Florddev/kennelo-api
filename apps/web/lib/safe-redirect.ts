export function safeRedirectPath(target: unknown): string | null {
    if (typeof target !== "string" || target.length === 0) return null;
    if (!target.startsWith("/")) return null;
    if (target.startsWith("//") || target.startsWith("/\\")) return null;
    return target;
}
