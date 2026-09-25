<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WeekDayEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plage d'ouverture habituelle. Un jour peut en avoir plusieurs (9 h - 12 h, puis 14 h - 18 h).
 *
 * @property string $id
 * @property string $activity_id
 * @property WeekDayEnum $weekday
 * @property string $opens_at
 * @property string $closes_at
 */
class ActivityOpeningHour extends Model
{
    use HasUuids;

    protected $fillable = [
        'activity_id',
        'weekday',
        'opens_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => WeekDayEnum::class,
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
