<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read AnimalType $animalType
 * @property-read ActivityCycle $cycle
 */
class ActivityCycleSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'activities_cycles_settings';

    protected $fillable = [
        'activity_cycle_id',
        'animal_type_id',
        'max_capacity',
        'price',
        'sum_weekdays',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sum_weekdays' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ActivityCycle::class, 'activity_cycle_id');
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }
}
