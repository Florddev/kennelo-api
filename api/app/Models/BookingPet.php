<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $price_per_night
 * @property int $number_of_nights
 * @property string $subtotal
 */
class BookingPet extends Pivot
{
    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'number_of_nights' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }
}
