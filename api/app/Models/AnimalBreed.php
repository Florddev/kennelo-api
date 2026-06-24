<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $id
 * @property string $animal_type_id
 * @property string $breed
 * @property string $label
 * @property-read AnimalType|null $animalType
 */
class AnimalBreed extends Model
{
    use HasTranslations, HasUuids;

    protected $fillable = [
        'animal_type_id',
        'breed',
        'label',
    ];

    public array $translatable = [
        'label',
    ];

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }
}
