<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $category
 */
class AnimalType extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'category',
    ];

    public array $translatable = [
        'name',
    ];

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function breeds(): HasMany
    {
        return $this->hasMany(AnimalBreed::class);
    }

    public function attributeDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(AttributeDefinition::class, 'attribute_animal_types');
    }
}
