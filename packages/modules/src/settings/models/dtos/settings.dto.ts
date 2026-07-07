export type SettingsDto = {
    fees: {
        user_service_fee_rate: string;
        host_commission_rate: string;
    };
    booking: {
        acceptance_window_hours: number;
        payout_delay_hours: number;
        reminder_after_hours: number;
    };
    stripe: {
        currency: string;
    };
    notifications: {
        tier3_enabled: boolean;
    };
    downgrade: {
        soft_disable_activities: boolean;
        soft_disable_cycles: boolean;
        soft_disable_photos: boolean;
    };
};
