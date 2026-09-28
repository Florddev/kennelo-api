<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $activity_id
 * @property int $up_to_km
 * @property numeric-string $fee
 */
class ActivityTravelFeeTier extends Model
{
    use HasUuids;

    protected $fillable = [
        'up_to_km',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'up_to_km' => 'integer',
            'fee' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
