export type SearchLogDto = {
    id: string;
    location: string | null;
    department: string | null;
    region: string | null;
    latitude: number | null;
    longitude: number | null;
    filters: Record<string, unknown> | null;
    results_count: number;
    user: { id: string; email: string } | null;
    created_at: string;
};
