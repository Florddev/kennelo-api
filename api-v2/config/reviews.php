<?php

declare(strict_types=1);

return [
    // Jours laissés au client et à l'équipe, après la fin de la réservation, pour donner leur avis. Les avis restent
    // cachés jusqu'à ce que les deux soient donnés, ou jusqu'à la fin de ce délai.
    'window_days' => (int) env('REVIEWS_WINDOW_DAYS', 14),

    // Avis publiés qu'il faut à une activité pour entrer dans la section « mieux notés » de la recherche.
    'top_rated_min_reviews' => (int) env('REVIEWS_TOP_RATED_MIN_REVIEWS', 3),
];
