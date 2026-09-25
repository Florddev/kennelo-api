<?php

declare(strict_types=1);

return [
    'greeting' => 'Bonjour,',
    'action' => 'Voir la réservation',

    'booking_created' => [
        'subject' => 'Nouvelle demande de réservation',
        'line' => 'Vous avez reçu une nouvelle demande de réservation pour :activity.',
    ],
    'booking_confirmed' => [
        'subject' => 'Réservation confirmée',
        'line' => 'Votre réservation pour :activity est confirmée.',
    ],
    'booking_rejected' => [
        'subject' => 'Réservation refusée',
        'line' => 'Votre réservation pour :activity a été refusée.',
    ],
    'booking_expired' => [
        'subject' => 'Réservation expirée',
        'line' => 'Votre demande pour :activity a expiré.',
    ],
    'booking_reminder' => [
        'subject' => 'Demande en attente de réponse',
        'line' => 'Une demande de réservation pour :activity attend votre réponse.',
    ],
    'booking_cancelled_by_client' => [
        'subject' => 'Réservation annulée par le client',
        'line' => 'Le client a annulé sa réservation pour :activity.',
    ],
    'booking_cancelled_by_pro' => [
        'subject' => 'Réservation annulée',
        'line' => 'Votre réservation pour :activity a été annulée par le professionnel. Vous êtes remboursé en totalité.',
    ],
    'payment_action_required' => [
        'subject' => 'Confirmez votre paiement',
        'line' => 'Un paiement complémentaire de :amount € pour :activity attend votre confirmation.',
    ],
    'payment_succeeded' => [
        'subject' => 'Paiement confirmé',
        'line' => 'Votre paiement de :amount € a été confirmé.',
    ],
    'payment_failed' => [
        'subject' => 'Échec du paiement',
        'line' => 'Votre paiement de :amount € a échoué.',
    ],
    'payment_refunded' => [
        'subject' => 'Remboursement effectué',
        'line' => 'Un remboursement de :amount € a été effectué.',
    ],
    'payout_sent' => [
        'subject' => 'Versement envoyé',
        'line' => 'Un versement de :amount € a été envoyé pour :activity.',
    ],
];
