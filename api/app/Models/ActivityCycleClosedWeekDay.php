<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ActivityCycle $cycle
 */
class ActivityCycleClosedWeekDay extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'activities_cycles_closed_week_days';

    protected $fillable = [
        'activity_cycle_id',
        'sum_weekdays',
    ];

    protected function casts(): array
    {
        return [
            'sum_weekdays' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ActivityCycle::class, 'activity_cycle_id');
    }
}
