<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property-read Activity $activity
 * @property-read Collection<int, ActivityCycleSetting> $settings
 * @property-read Collection<int, ActivityCycleClosedWeekDay> $closedWeekDays
 */
class ActivityCycle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'activities_cycles';

    protected $fillable = [
        'activity_id',
        'start_date',
        'end_date',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ActivityCycleSetting::class);
    }

    public function closedWeekDays(): HasMany
    {
        return $this->hasMany(ActivityCycleClosedWeekDay::class);
    }
}
