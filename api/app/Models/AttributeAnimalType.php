<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeAnimalType extends Model
{
    public $incrementing = false;

    public $keyType = 'string';

    protected $primaryKey = null;

    public $timestamps = false;

    protected $fillable = [
        'attribute_definition_id',
        'animal_type_id',
    ];

    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }
}
