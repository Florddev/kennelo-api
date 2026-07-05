import { api } from "@workspace/common";

import type { ProspectImportDto } from "../../models/dtos/prospect-import.dto";
import { ProspectImportModel } from "../../models/prospect-import.model";

export async function getProspectImport(importId: string): Promise<ProspectImportModel | null> {
    const response = await api.get<ProspectImportDto>(`/admin/prospects/imports/${importId}`);

    if (!response.data) return null;
    return ProspectImportModel.from(response.data);
}
