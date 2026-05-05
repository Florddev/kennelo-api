<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $review_id
 * @property string $criteria_code
 * @property string $score
 * @property-read Review|null $review
 * @property-read ReviewCriteriaDefinition|null $definition
 */
class ReviewCriteriaScore extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'review_id',
        'criteria_code',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:1',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReviewCriteriaDefinition::class, 'criteria_code', 'code');
    }
}
