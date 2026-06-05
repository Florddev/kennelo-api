<?php

declare(strict_types=1);

namespace App\Enums;

enum EstablishmentTypeEnum: string
{
    case BOARDING = 'boarding';
    case BREEDING = 'breeding';
    case DAYCARE = 'daycare';
    case SHELTER = 'shelter';
    case OTHER = 'other';
    case PET_SITTER = 'pet-sitter';
    case HOME_CARE = 'home-care';
    case HOST_FAMILY = 'host-family';
    case MOBILE_BOARDING = 'mobile-boarding';
}
