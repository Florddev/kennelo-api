<?php

declare(strict_types=1);

return [
    'greeting' => 'Bonjour,',
    'reason' => 'Motif : :reason',
    'until' => 'Jusqu\'au :date.',

    'actions' => [
        'booking' => 'Voir la réservation',
        'hosting_booking' => 'Voir la réservation',
        'review' => 'Laisser un avis',
        'conversation' => 'Lire le message',
        'organization' => 'Voir mon entreprise',
        'activity' => 'Voir l\'activité',
        'invitations' => 'Voir l\'invitation',
        'subscription' => 'Gérer l\'abonnement',
        'admin_booking' => 'Ouvrir dans le back-office',
    ],

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
    'booking_cancelled_by_platform' => [
        'subject' => 'Réservation annulée par Kennelo',
        'line' => 'Kennelo a annulé votre réservation pour :activity.',
    ],
    'team_booking_cancelled_by_platform' => [
        'subject' => 'Réservation annulée par Kennelo',
        'line' => 'Kennelo a annulé une réservation pour :activity.',
    ],
    'booking_completed' => [
        'subject' => 'Comment s\'est passée votre réservation ?',
        'line' => 'Votre réservation pour :activity est terminée. Votre avis aide les autres propriétaires d\'animaux à choisir.',
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
    'dispute_opened' => [
        'subject' => 'Paiement contesté',
        'line' => 'Un client conteste auprès de sa banque un paiement de :amount € pour :activity. Répondez avant le :date depuis le tableau de bord Stripe.',
    ],
    'booking_disputed' => [
        'subject' => 'Paiement contesté par un client',
        'line' => 'Un client conteste auprès de sa banque un paiement de :amount € pour :activity. Le versement de cette réservation est suspendu jusqu\'à la décision.',
    ],
    'booking_dispute_won' => [
        'subject' => 'Contestation close en votre faveur',
        'line' => 'La banque du client a donné raison à Kennelo pour :activity : le versement de la réservation reprend.',
    ],
    'booking_dispute_lost' => [
        'subject' => 'Contestation perdue',
        'line' => 'La banque du client lui a donné raison pour :activity : :amount € sont retirés de ce qui vous revient pour cette réservation.',
    ],
    'new_message' => [
        'subject' => 'Nouveau message de :sender',
        'line' => ':sender vous a écrit au sujet de :activity : « :preview »',
    ],
    'organization_approved' => [
        'subject' => 'Votre entreprise est validée',
        'line' => 'Kennelo a validé :organization. Vos activités pourront être réservées une fois validées à leur tour.',
    ],
    'organization_rejected' => [
        'subject' => 'Votre entreprise n\'a pas été validée',
        'line' => 'Kennelo n\'a pas pu valider :organization.',
    ],
    'organization_suspended' => [
        'subject' => 'Votre entreprise est suspendue',
        'line' => ':organization est suspendue : ses activités n\'apparaissent plus dans la recherche.',
    ],
    'stripe_account_activated' => [
        'subject' => 'Paiements activés',
        'line' => 'Le compte de paiement de :organization est activé : vos activités peuvent recevoir des réservations.',
    ],
    'activity_approved' => [
        'subject' => 'Votre activité est validée',
        'line' => 'Kennelo a validé :activity.',
    ],
    'activity_rejected' => [
        'subject' => 'Votre activité n\'a pas été validée',
        'line' => 'Kennelo n\'a pas pu valider :activity.',
    ],
    'activity_suspended' => [
        'subject' => 'Votre activité est suspendue',
        'line' => ':activity est suspendue et n\'apparaît plus dans la recherche.',
    ],
    'activity_document_rejected' => [
        'subject' => 'Justificatif refusé',
        'line' => 'Un justificatif de :activity a été refusé. Déposez-en un nouveau pour que l\'activité reste réservable.',
    ],
    'activity_document_expiring' => [
        'subject' => 'Un justificatif expire bientôt',
        'line' => 'Un justificatif de :activity expire le :date. Déposez-en un nouveau avant cette date pour que l\'activité reste réservable.',
    ],
    'activity_document_expired' => [
        'subject' => 'Justificatif expiré',
        'line' => 'Un justificatif obligatoire de :activity a expiré : l\'activité n\'apparaît plus dans la recherche tant qu\'il n\'est pas remplacé.',
    ],
    'member_invited' => [
        'subject' => 'Invitation à rejoindre :organization',
        'line' => 'Vous êtes invité à rejoindre l\'équipe de :organization sur Kennelo.',
    ],
    'subscription_activated' => [
        'subject' => 'Abonnement activé',
        'line' => 'L\'abonnement de :organization est actif.',
    ],
    'subscription_payment_failed' => [
        'subject' => 'Échec du paiement de l\'abonnement',
        'line' => 'Le paiement de l\'abonnement de :organization a échoué. Mettez à jour votre moyen de paiement pour garder votre offre.',
    ],
    'subscription_downgraded' => [
        'subject' => 'Votre abonnement a pris fin',
        'line' => 'L\'abonnement de :organization a pris fin : elle revient à l\'offre gratuite et à ses limites.',
    ],
    'account_banned' => [
        'subject' => 'Votre compte est suspendu',
        'line' => 'Votre compte Kennelo a été suspendu.',
    ],
    'account_unbanned' => [
        'subject' => 'Votre compte est réactivé',
        'line' => 'Votre compte Kennelo est de nouveau actif.',
    ],
];
