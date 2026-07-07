import type { SettingsDto } from "./dtos/settings.dto";

export class SettingsModel {
    private constructor(
        public readonly userServiceFeeRate: string,
        public readonly hostCommissionRate: string,
        public readonly acceptanceWindowHours: number,
        public readonly payoutDelayHours: number,
        public readonly reminderAfterHours: number,
        public readonly currency: string,
        public readonly tier3Enabled: boolean,
        public readonly softDisableActivities: boolean,
        public readonly softDisableCycles: boolean,
        public readonly softDisablePhotos: boolean,
    ) {}

    static from(dto: SettingsDto): SettingsModel {
        return new SettingsModel(
            dto.fees.user_service_fee_rate,
            dto.fees.host_commission_rate,
            dto.booking.acceptance_window_hours,
            dto.booking.payout_delay_hours,
            dto.booking.reminder_after_hours,
            dto.stripe.currency,
            dto.notifications.tier3_enabled,
            dto.downgrade.soft_disable_activities,
            dto.downgrade.soft_disable_cycles,
            dto.downgrade.soft_disable_photos,
        );
    }
}
