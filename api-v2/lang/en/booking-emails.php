<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello,',
    'action' => 'View the booking',

    'booking_created' => [
        'subject' => 'New booking request',
        'line' => 'You received a new booking request for :activity.',
    ],
    'booking_confirmed' => [
        'subject' => 'Booking confirmed',
        'line' => 'Your booking for :activity is confirmed.',
    ],
    'booking_rejected' => [
        'subject' => 'Booking declined',
        'line' => 'Your booking for :activity was declined.',
    ],
    'booking_expired' => [
        'subject' => 'Booking expired',
        'line' => 'Your request for :activity has expired.',
    ],
    'booking_reminder' => [
        'subject' => 'A request is waiting for your answer',
        'line' => 'A booking request for :activity is waiting for your answer.',
    ],
    'booking_cancelled_by_client' => [
        'subject' => 'Booking cancelled by the client',
        'line' => 'The client cancelled their booking for :activity.',
    ],
    'booking_cancelled_by_pro' => [
        'subject' => 'Booking cancelled',
        'line' => 'Your booking for :activity was cancelled by the professional. You will be refunded in full.',
    ],
    'payment_action_required' => [
        'subject' => 'Confirm your payment',
        'line' => 'An extra payment of :amount € for :activity needs your confirmation.',
    ],
    'payment_succeeded' => [
        'subject' => 'Payment confirmed',
        'line' => 'Your payment of :amount € has been confirmed.',
    ],
    'payment_failed' => [
        'subject' => 'Payment failed',
        'line' => 'Your payment of :amount € has failed.',
    ],
    'payment_refunded' => [
        'subject' => 'Refund issued',
        'line' => 'A refund of :amount € has been issued.',
    ],
    'payout_sent' => [
        'subject' => 'Payout sent',
        'line' => 'A payout of :amount € was sent for :activity.',
    ],
];
