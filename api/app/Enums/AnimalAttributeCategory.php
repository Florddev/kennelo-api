<?php

declare(strict_types=1);

namespace App\Enums;

enum AnimalAttributeCategory: string
{
    case INFO = 'info';
    case BEHAVIOR = 'behavior';
    case SOCIAL = 'social';
    case HYGIENE = 'hygiene';
    case CARE = 'care';
    case HEALTH = 'health';
    case HABITAT = 'habitat';
    case DIET = 'diet';
}
