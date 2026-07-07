import { api } from "@workspace/common";
import type { SettingsDto } from "../../models/dtos/settings.dto";
import { SettingsModel } from "../../models/settings.model";
import type { UpdateSettingsInput } from "../../validators/settings.schema";

export async function updateSettings(input: UpdateSettingsInput): Promise<SettingsModel | null> {
    const response = await api.put<SettingsDto>("/admin/settings", {
        values: {
            user_service_fee_rate: String(input.userServiceFeeRate),
            host_commission_rate: String(input.hostCommissionRate),
            acceptance_window_hours: input.acceptanceWindowHours,
            payout_delay_hours: input.payoutDelayHours,
            reminder_after_hours: input.reminderAfterHours,
            currency: input.currency,
            tier3_enabled: input.tier3Enabled,
            soft_disable_activities: input.softDisableActivities,
            soft_disable_cycles: input.softDisableCycles,
            soft_disable_photos: input.softDisablePhotos,
        },
    });

    if (!response.data) {
        return null;
    }

    return SettingsModel.from(response.data);
}
