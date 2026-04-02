export const ILLUSTRATED_TYPES = ["dog", "cat", "bird", "amphibian", "rabbit", "reptile"] as const;

export type IllustratedType = (typeof ILLUSTRATED_TYPES)[number];

export function isIllustratedType(code: string): code is IllustratedType {
    return ILLUSTRATED_TYPES.includes(code as IllustratedType);
}
