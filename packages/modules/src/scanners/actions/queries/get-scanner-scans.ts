import { api } from "@workspace/common";

import type { ScannerScanDto } from "../../models/dtos/scanner-scan.dto";
import { ScannerScanModel } from "../../models/scanner-scan.model";

export async function getScannerScans(): Promise<ScannerScanModel[]> {
    const response = await api.get<ScannerScanDto[]>("/user/scanner-scans");

    if (!response.data) {
        return [];
    }

    return response.data.map(ScannerScanModel.from);
}
