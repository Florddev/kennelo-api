export type ProspectImportStatusValue = "pending" | "processing" | "completed" | "failed";

export type ProspectImportDto = {
    id: string;
    location: string;
    max_results: number;
    status: ProspectImportStatusValue;
    imported_count: number;
    skipped_count: number;
    error: string | null;
    finished_at: string | null;
    created_at: string;
};
