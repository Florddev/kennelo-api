import { api } from "@workspace/common";

import type { ScannerDto } from "../../models/dtos/scanner.dto";
import { ScannerModel } from "../../models/scanner.model";

export async function getScanners(): Promise<ScannerModel[]> {
    const response = await api.get<ScannerDto[]>("/user/scanners");

    if (!response.data) {
        return [];
    }

    return response.data.map(ScannerModel.from);
}
