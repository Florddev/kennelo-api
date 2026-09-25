<?php

declare(strict_types=1);

return [
    // Fuseau horaire d'une activité créée sans le préciser.
    'default_timezone' => env('ACTIVITY_DEFAULT_TIMEZONE', 'Europe/Paris'),

    // Rayon de déplacement maximal d'un pro, et rayon de recherche maximal, en kilomètres.
    'max_radius_km' => (int) env('ACTIVITY_MAX_RADIUS_KM', 200),

    // Rayon de recherche autour du client quand il n'en précise pas, en kilomètres.
    'default_search_radius_km' => (int) env('ACTIVITY_DEFAULT_SEARCH_RADIUS_KM', 30),

    'documents' => [
        // Disque privé : un justificatif ne se lit qu'au travers de l'API.
        'disk' => env('ACTIVITY_DOCUMENTS_DISK', 'local'),

        // L'entreprise est prévenue ce nombre de jours avant l'échéance d'un justificatif.
        'expiry_notice_days' => (int) env('ACTIVITY_DOCUMENTS_EXPIRY_NOTICE_DAYS', 30),
    ],
];
