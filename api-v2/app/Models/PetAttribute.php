<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $value_date
 */
class PetAttribute extends Model
{
    use HasUuids;

    protected $fillable = [
        'pet_id',
        'attribute_definition_id',
        'attribute_option_id',
        'value_text',
        'value_integer',
        'value_decimal',
        'value_boolean',
        'value_date',
    ];

    protected function casts(): array
    {
        return [
            'value_decimal' => 'decimal:2',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class);
    }

    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
    }
}
