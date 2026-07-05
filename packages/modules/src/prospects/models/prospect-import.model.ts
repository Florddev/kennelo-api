import type { ProspectImportDto, ProspectImportStatusValue } from "./dtos/prospect-import.dto";

export class ProspectImportModel {
    private constructor(
        public readonly id: string,
        public readonly location: string,
        public readonly maxResults: number,
        public readonly status: ProspectImportStatusValue,
        public readonly importedCount: number,
        public readonly skippedCount: number,
        public readonly error: string | null,
        public readonly finishedAt: string | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: ProspectImportDto): ProspectImportModel {
        return new ProspectImportModel(
            dto.id,
            dto.location,
            dto.max_results,
            dto.status,
            dto.imported_count ?? 0,
            dto.skipped_count ?? 0,
            dto.error ?? null,
            dto.finished_at ?? null,
            dto.created_at,
        );
    }

    isFinished(): boolean {
        return this.status === "completed" || this.status === "failed";
    }
}
