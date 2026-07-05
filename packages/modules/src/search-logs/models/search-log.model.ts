import type { SearchLogDto } from "./dtos/search-log.dto";

export class SearchLogModel {
    private constructor(
        public readonly id: string,
        public readonly location: string | null,
        public readonly department: string | null,
        public readonly region: string | null,
        public readonly resultsCount: number,
        public readonly filters: Record<string, unknown> | null,
        public readonly userEmail: string | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: SearchLogDto): SearchLogModel {
        return new SearchLogModel(
            dto.id,
            dto.location ?? null,
            dto.department ?? null,
            dto.region ?? null,
            dto.results_count ?? 0,
            dto.filters ?? null,
            dto.user?.email ?? null,
            dto.created_at,
        );
    }
}
