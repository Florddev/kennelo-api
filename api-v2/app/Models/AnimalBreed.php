<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $id
 * @property string $animal_type_id
 * @property string $breed
 * @property string $label
 * @property PetSizeClassEnum|null $default_size_class
 * @property PetCoatTypeEnum|null $default_coat_type
 * @property-read AnimalType|null $animalType
 */
class AnimalBreed extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'animal_type_id',
        'breed',
        'label',
        'default_size_class',
        'default_coat_type',
    ];

    public array $translatable = [
        'label',
    ];

    /**
     * Taille et poil par défaut, utilisés par la grille de prix quand la fiche de l'animal ne les précise pas.
     */
    protected function casts(): array
    {
        return [
            'default_size_class' => PetSizeClassEnum::class,
            'default_coat_type' => PetCoatTypeEnum::class,
        ];
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }
}
