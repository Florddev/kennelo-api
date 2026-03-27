<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnimalAttributeCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'label',
        'category',
        'value_type',
        'has_predefined_options',
        'is_required',
        'validation_rules',
    ];

    protected $casts = [
        'category' => AnimalAttributeCategory::class,
        'has_predefined_options' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }

    public function petAttributes(): HasMany
    {
        return $this->hasMany(PetAttribute::class);
    }

    public function animalTypes(): BelongsToMany
    {
        return $this->belongsToMany(AnimalType::class, 'attribute_animal_types');
    }
}
