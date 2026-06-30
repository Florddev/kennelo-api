<?php

declare(strict_types=1);

return [
    'user_service_fee_rate' => env('BOOKING_USER_SERVICE_FEE_RATE', '0.08'),
    'host_commission_rate' => env('BOOKING_HOST_COMMISSION_RATE', '0.06'),
    'acceptance_window_hours' => env('BOOKING_ACCEPTANCE_WINDOW_HOURS', 72),
    'payout_delay_hours' => env('BOOKING_PAYOUT_DELAY_HOURS', 24),
];
