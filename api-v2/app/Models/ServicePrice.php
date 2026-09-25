<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de la grille d'une prestation : un prix TTC et une durée pour une espèce, précisée ou non par la taille,
 * le poil ou la race. Pour un animal, la ligne la plus précise l'emporte (App\Services\Catalog\ServicePriceResolver).
 *
 * @property string $id
 * @property string $service_id
 * @property string $animal_type_id
 * @property PetSizeClassEnum|null $size_class
 * @property PetCoatTypeEnum|null $coat_type
 * @property string|null $animal_breed_id
 * @property numeric-string $price
 * @property int|null $duration_minutes
 */
class ServicePrice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'service_id',
        'animal_type_id',
        'size_class',
        'coat_type',
        'animal_breed_id',
        'price',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'size_class' => PetSizeClassEnum::class,
            'coat_type' => PetCoatTypeEnum::class,
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<AnimalType, $this>
     */
    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    /**
     * @return BelongsTo<AnimalBreed, $this>
     */
    public function animalBreed(): BelongsTo
    {
        return $this->belongsTo(AnimalBreed::class);
    }
}
