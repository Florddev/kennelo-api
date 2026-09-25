<?php

declare(strict_types=1);

return [
    // Écart entre deux créneaux proposés, en minutes. Les créneaux tombent sur des multiples de cet écart
    // (9 h, 9 h 15, 9 h 30…), dans le fuseau de l'activité.
    'slot_step_minutes' => (int) env('AGENDA_SLOT_STEP_MINUTES', 15),

    // Délai de prévenance : un rendez-vous se prend au plus tard ce nombre de minutes avant son début.
    'min_notice_minutes' => (int) env('AGENDA_MIN_NOTICE_MINUTES', 120),

    // Période maximale d'une recherche de créneaux ou d'une vue agenda, en jours.
    'max_range_days' => (int) env('AGENDA_MAX_RANGE_DAYS', 31),

    // Nombre maximal d'animaux dans un même rendez-vous.
    'max_pets_per_appointment' => (int) env('AGENDA_MAX_PETS_PER_APPOINTMENT', 5),
];
