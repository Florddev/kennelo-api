<?php

declare(strict_types=1);

return [
    'greeting' => 'Hallo,',
    'action' => 'Buchung ansehen',

    'booking_created' => [
        'subject' => 'Neue Buchungsanfrage',
        'line' => 'Sie haben eine neue Buchungsanfrage für :activity erhalten.',
    ],
    'booking_confirmed' => [
        'subject' => 'Buchung bestätigt',
        'line' => 'Ihre Buchung für :activity ist bestätigt.',
    ],
    'booking_rejected' => [
        'subject' => 'Buchung abgelehnt',
        'line' => 'Ihre Buchung für :activity wurde abgelehnt.',
    ],
    'booking_expired' => [
        'subject' => 'Buchung abgelaufen',
        'line' => 'Ihre Anfrage für :activity ist abgelaufen.',
    ],
    'booking_reminder' => [
        'subject' => 'Eine Anfrage wartet auf Ihre Antwort',
        'line' => 'Eine Buchungsanfrage für :activity wartet auf Ihre Antwort.',
    ],
    'payment_succeeded' => [
        'subject' => 'Zahlung bestätigt',
        'line' => 'Ihre Zahlung von :amount € wurde bestätigt.',
    ],
    'payment_failed' => [
        'subject' => 'Zahlung fehlgeschlagen',
        'line' => 'Ihre Zahlung von :amount € ist fehlgeschlagen.',
    ],
    'payment_refunded' => [
        'subject' => 'Rückerstattung veranlasst',
        'line' => 'Eine Rückerstattung von :amount € wurde veranlasst.',
    ],
    'payout_sent' => [
        'subject' => 'Auszahlung gesendet',
        'line' => 'Eine Auszahlung von :amount € wurde für :activity gesendet.',
    ],
];
