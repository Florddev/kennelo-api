<?php

declare(strict_types=1);

return [
    // Identité de Kennelo, copiée dans chaque facture qu'il émet ou qu'il émet pour le compte d'une entreprise.
    'kennelo' => [
        'legal_name' => env('BILLING_KENNELO_LEGAL_NAME', 'Kennelo'),
        'legal_form' => env('BILLING_KENNELO_LEGAL_FORM', 'SAS'),
        'siren' => env('BILLING_KENNELO_SIREN'),
        'siret' => env('BILLING_KENNELO_SIRET'),
        'vat_number' => env('BILLING_KENNELO_VAT_NUMBER'),
        'address' => [
            'line1' => env('BILLING_KENNELO_ADDRESS_LINE1'),
            'line2' => env('BILLING_KENNELO_ADDRESS_LINE2'),
            'postal_code' => env('BILLING_KENNELO_POSTAL_CODE'),
            'city' => env('BILLING_KENNELO_CITY'),
            'country' => env('BILLING_KENNELO_COUNTRY', 'FR'),
        ],
        // TVA des frais de service et de la commission. À faire confirmer par un comptable.
        'vat_rate' => env('BILLING_KENNELO_VAT_RATE', '20.00'),
    ],

    // Préfixe des numéros : KEN-2026-000001 pour les factures de Kennelo, KNL-2026-000001 pour celles qu'il émet
    // au nom des entreprises (une série à part, qui ne se mêle pas à leur propre facturation).
    'number_prefixes' => [
        'kennelo' => env('BILLING_KENNELO_NUMBER_PREFIX', 'KEN'),
        'organization' => env('BILLING_ORGANIZATION_NUMBER_PREFIX', 'KNL'),
    ],

    // Fuseau des dates de facture, de l'année de numérotation et des mois du récapitulatif de commission.
    'timezone' => env('BILLING_TIMEZONE', 'Europe/Paris'),

    // Langue des libellés écrits dans les factures : des pièces comptables françaises.
    'locale' => 'fr',

    // Disque privé des PDF : ils ne se téléchargent que par l'API, qui vérifie les droits.
    'pdf_disk' => env('BILLING_PDF_DISK', 'local'),
];
