import { api } from "@workspace/common";

import type { HostScanLookupDto } from "../../models/dtos/host-scan-lookup.dto";
import { HostScanLookupModel } from "../../models/host-scan-lookup.model";

export async function getHostScanLookup(
    microchipNumber: string,
): Promise<HostScanLookupModel | null> {
    const response = await api.get<HostScanLookupDto>(
        `/hosting/scan-lookup/${encodeURIComponent(microchipNumber)}`,
    );

    if (!response.data) {
        return null;
    }

    return HostScanLookupModel.from(response.data);
}
