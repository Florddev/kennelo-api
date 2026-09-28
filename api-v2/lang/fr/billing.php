<?php

declare(strict_types=1);

return [
    'mentions' => [
        'vat_franchise' => 'TVA non applicable, art. 293 B du CGI',
    ],
    'lines' => [
        'stay' => 'Séjour (:unit) du :start au :end',
        'travel_fee' => 'Frais de déplacement',
        'item' => ':service',
        'item_pet' => ':service pour :pet',
        'item_scheduled' => ':service, le :date à :time',
        'item_pet_scheduled' => ':service pour :pet, le :date à :time',
        'service_fee' => 'Frais de service Kennelo, réservation chez :activity le :start',
        'commission' => 'Commission Kennelo, réservation :reference chez :activity du :start au :end',
        'refund' => [
            'client_cancellation' => 'Remboursement après annulation par le client (facture :number)',
            'pro_cancellation' => 'Remboursement après annulation par le professionnel (facture :number)',
            'adjustment' => 'Remboursement d\'une prestation retirée (facture :number)',
        ],
    ],
    'pdf' => [
        'title' => [
            'invoice' => 'Facture',
            'credit_note' => 'Avoir',
        ],
        'number' => 'N° :number',
        'issued_on' => 'Date : :date',
        'booking' => 'Réservation :reference',
        'issuer' => 'Émetteur',
        'recipient' => 'Destinataire',
        'siren' => 'SIREN :value',
        'siret' => 'SIRET :value',
        'vat_number' => 'N° de TVA :value',
        'mandate' => [
            'invoice' => 'Facture émise par :kennelo au nom et pour le compte de :issuer, en vertu du mandat de facturation accepté le :date.',
            'credit_note' => 'Avoir émis par :kennelo au nom et pour le compte de :issuer, en vertu du mandat de facturation accepté le :date.',
        ],
        'credits' => 'Avoir sur la facture n° :number du :date.',
        'period' => 'Période : du :start au :end.',
        'columns' => [
            'description' => 'Désignation',
            'quantity' => 'Qté',
            'unit_price_ttc' => 'Prix unitaire TTC',
            'vat_rate' => 'TVA',
            'total_ht' => 'Total HT',
            'total_ttc' => 'Total TTC',
        ],
        'totals' => [
            'ht' => 'Total HT',
            'vat' => 'TVA',
            'ttc' => 'Total TTC',
        ],
        'settlement' => [
            'paid' => 'Facture acquittée : payée en ligne lors de la réservation.',
            'withheld' => 'Montant retenu sur les versements de la période.',
            'refunded' => "Montant remboursé sur le moyen de paiement d'origine.",
        ],
    ],
];
