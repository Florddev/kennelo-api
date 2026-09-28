<?php

declare(strict_types=1);

return [
    'tier3_enabled' => (bool) env('NOTIFICATIONS_TIER3_ENABLED', false),

    'message_email_delay_minutes' => (int) env('NOTIFICATIONS_MESSAGE_EMAIL_DELAY_MINUTES', 15),

    'links' => [
        'booking' => ':frontend/bookings/:booking_id',
        'hosting_booking' => ':frontend/hosting/bookings/:booking_id',
        'review' => ':frontend/bookings/:booking_id/review',
        'conversation' => ':frontend/messages/:conversation_id',
        'organization' => ':frontend/hosting/organizations/:organization_id',
        'activity' => ':frontend/hosting/activities/:activity_id',
        'invitations' => ':frontend/hosting/invitations',
        'subscription' => ':frontend/hosting/organizations/:organization_id/subscription',
        'admin_booking' => ':back_office/bookings/:booking_id',
    ],
];
