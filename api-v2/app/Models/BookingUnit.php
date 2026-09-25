<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Place occupée pendant un séjour, avec le détail de son prix nuit par nuit (price_breakdown).
 * Une ligne par place : les animaux qui la partagent y sont rattachés par booking_pets.
 *
 * @property string $id
 * @property string $booking_id
 * @property string $activity_unit_type_id
 * @property int $quantity
 * @property int $nights
 * @property numeric-string $subtotal
 * @property list<array{date: string, pricing_period_id: string, price: string, extra_animals_price: string}> $price_breakdown
 * @property-read ActivityUnitType|null $unitType
 */
class BookingUnit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_unit_type_id',
        'quantity',
        'nights',
        'subtotal',
        'price_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'nights' => 'integer',
            'subtotal' => 'decimal:2',
            'price_breakdown' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<ActivityUnitType, $this>
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(ActivityUnitType::class, 'activity_unit_type_id');
    }

    /**
     * @return BelongsToMany<Pet, $this>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'booking_pets', 'booking_unit_id');
    }
}
