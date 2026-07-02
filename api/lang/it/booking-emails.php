<?php

declare(strict_types=1);

return [
    'greeting' => 'Ciao,',
    'action' => 'Vedi la prenotazione',

    'booking_created' => [
        'subject' => 'Nuova richiesta di prenotazione',
        'line' => 'Hai ricevuto una nuova richiesta di prenotazione per :activity.',
    ],
    'booking_confirmed' => [
        'subject' => 'Prenotazione confermata',
        'line' => 'La tua prenotazione per :activity è confermata.',
    ],
    'booking_rejected' => [
        'subject' => 'Prenotazione rifiutata',
        'line' => 'La tua prenotazione per :activity è stata rifiutata.',
    ],
    'booking_expired' => [
        'subject' => 'Prenotazione scaduta',
        'line' => 'La tua richiesta per :activity è scaduta.',
    ],
    'booking_reminder' => [
        'subject' => 'Una richiesta attende la tua risposta',
        'line' => 'Una richiesta di prenotazione per :activity attende la tua risposta.',
    ],
    'payment_succeeded' => [
        'subject' => 'Pagamento confermato',
        'line' => 'Il tuo pagamento di :amount € è stato confermato.',
    ],
    'payment_failed' => [
        'subject' => 'Pagamento non riuscito',
        'line' => 'Il tuo pagamento di :amount € non è riuscito.',
    ],
    'payment_refunded' => [
        'subject' => 'Rimborso effettuato',
        'line' => 'Un rimborso di :amount € è stato effettuato.',
    ],
    'payout_sent' => [
        'subject' => 'Versamento inviato',
        'line' => 'Un versamento di :amount € è stato inviato per :activity.',
    ],
];
