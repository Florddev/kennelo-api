<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CriteriaApplicableTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $code
 * @property string $label
 * @property CriteriaApplicableTo $applicable_to
 * @property int $sort_order
 */
class ReviewCriteriaDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'label',
        'applicable_to',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'applicable_to' => CriteriaApplicableTo::class,
            'sort_order' => 'integer',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewCriteriaScore::class, 'criteria_code', 'code');
    }
}
