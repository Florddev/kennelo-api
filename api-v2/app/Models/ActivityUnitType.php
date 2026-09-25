<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Type de place d'un séjour (Box, Chambre chat…) : combien l'activité en a, et combien d'animaux d'un même
 * foyer peuvent la partager. Les espèces acceptées sont prises parmi celles de l'activité.
 *
 * @property string $id
 * @property string $activity_id
 * @property string $name
 * @property string|null $description
 * @property int $quantity
 * @property int $max_animals_per_unit
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Activity|null $activity
 */
class ActivityUnitType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'max_animals_per_unit',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'max_animals_per_unit' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return BelongsToMany<AnimalType, $this>
     */
    public function animalTypes(): BelongsToMany
    {
        return $this->belongsToMany(AnimalType::class, 'activity_unit_type_animal_types');
    }

    /**
     * @return HasMany<ActivityPeriodPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ActivityPeriodPrice::class);
    }

    /**
     * @return HasMany<BookingUnit, $this>
     */
    public function bookingUnits(): HasMany
    {
        return $this->hasMany(BookingUnit::class);
    }
}
