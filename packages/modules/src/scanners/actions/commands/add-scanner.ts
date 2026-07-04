import { api } from "@workspace/common";

import type { ScannerDto } from "../../models/dtos/scanner.dto";
import { ScannerModel } from "../../models/scanner.model";
import type { AddScannerInput } from "../../validators/scanner.schema";

export async function addScanner(input: AddScannerInput): Promise<ScannerModel> {
    const response = await api.post<ScannerDto>("/user/scanners", {
        code: input.code,
        name: input.name ?? null,
    });

    if (!response.data) {
        throw new Error("Failed to add scanner");
    }

    return ScannerModel.from(response.data);
}
