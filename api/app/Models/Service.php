<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read BookingServicePivot $booking_service
 */
class Service extends Model
{
    use HasUuids;

    protected $fillable = [
        'activity_id',
        'animal_type_id',
        'name',
        'description',
        'is_included',
        'price',
    ];

    protected $casts = [
        'is_included' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }
}
