import { api } from "@workspace/common";

import type { ProspectImportDto } from "../../models/dtos/prospect-import.dto";
import { ProspectImportModel } from "../../models/prospect-import.model";
import type { ImportProspectsInput } from "../../validators/import-prospects.schema";

export async function importProspects(
    input: ImportProspectsInput,
): Promise<ProspectImportModel | null> {
    const response = await api.post<ProspectImportDto>("/admin/prospects/import", {
        location: input.location,
        search_terms: input.searchTerms ?? undefined,
        max_results: input.maxResults ?? undefined,
    });

    if (!response.data) return null;
    return ProspectImportModel.from(response.data);
}
