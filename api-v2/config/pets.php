<?php

declare(strict_types=1);

// Taille déduite du poids (kg) quand la fiche de l'animal ne la précise pas, utilisée par la grille de prix.
// Pour chaque espèce, le poids maximal de chaque taille, de la plus petite à la plus grande ; null = sans limite.
// Une espèce absente n'a pas de taille déduite du poids : on se rabat sur la race.
return [
    'size_thresholds' => [
        'dog' => ['small' => 10, 'medium' => 25, 'large' => 45, 'giant' => null],
        'cat' => ['small' => 4, 'medium' => 7, 'large' => null],
    ],
];
