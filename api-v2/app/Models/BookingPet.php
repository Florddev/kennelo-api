<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Animal d'une réservation, avec la place qu'il occupe pendant un séjour.
 *
 * @property string $booking_id
 * @property string $pet_id
 * @property string|null $booking_unit_id
 */
class BookingPet extends Pivot
{
    protected $table = 'booking_pets';
}
