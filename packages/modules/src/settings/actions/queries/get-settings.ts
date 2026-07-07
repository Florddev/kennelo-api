import { api } from "@workspace/common";
import type { SettingsDto } from "../../models/dtos/settings.dto";
import { SettingsModel } from "../../models/settings.model";

export async function getSettings(): Promise<SettingsModel | null> {
    const response = await api.get<SettingsDto>("/admin/settings");

    if (!response.data) {
        return null;
    }

    return SettingsModel.from(response.data);
}
