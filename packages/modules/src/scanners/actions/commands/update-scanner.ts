import { api } from "@workspace/common";

import type { ScannerDto } from "../../models/dtos/scanner.dto";
import { ScannerModel } from "../../models/scanner.model";

export async function updateScanner(id: string, name: string | null): Promise<ScannerModel> {
    const response = await api.put<ScannerDto>(`/user/scanners/${id}`, { name });

    return ScannerModel.from(response.data!);
}
