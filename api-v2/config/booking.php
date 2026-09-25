<?php

declare(strict_types=1);

return [
    'user_service_fee_rate' => env('BOOKING_USER_SERVICE_FEE_RATE', '0.08'),
    'host_commission_rate' => env('BOOKING_HOST_COMMISSION_RATE', '0.06'),
    'acceptance_window_hours' => env('BOOKING_ACCEPTANCE_WINDOW_HOURS', 72),
    'payout_delay_hours' => env('BOOKING_PAYOUT_DELAY_HOURS', 24),
    'reminder_after_hours' => env('BOOKING_REMINDER_AFTER_HOURS', 36),
    // Taux de TVA des prestations d'une entreprise au régime normal ; 0 % en franchise. Figé sur chaque réservation.
    'vat_rate' => env('BOOKING_VAT_RATE', '20.00'),
    // Durée maximale d'un séjour, en nuits ou en jours.
    'max_stay_days' => (int) env('BOOKING_MAX_STAY_DAYS', 90),
];
