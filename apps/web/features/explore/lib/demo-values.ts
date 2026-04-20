function hashId(id: string): number {
    let hash = 0;
    for (let i = 0; i < id.length; i++) {
        hash = (Math.imul(hash, 31) + id.charCodeAt(i)) | 0;
    }
    return Math.abs(hash);
}

export function demoRating(id: string): string {
    const h = hashId(id);
    const rating = 4.4 + (h % 60) / 100;
    return rating.toFixed(2).replace(".", ",");
}

export function demoReviewsCount(id: string): number {
    const h = hashId(id);
    return 30 + (h % 900);
}

export function demoFallbackPrice(id: string): number {
    const h = hashId(id);
    return 18 + (h % 35);
}

export function demoImage(id: string, width: number, height: number, variant = 0): string {
    return `https://picsum.photos/seed/kennelo-${id}-${variant}/${width}/${height}`;
}

export function demoYearsHosting(id: string): number {
    const h = hashId(id);
    return 1 + (h % 14);
}
