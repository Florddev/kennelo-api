<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AvailabilityStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $date
 * @property AvailabilityStatusEnum $status
 * @property-read Activity $activity
 */
class ActivityAvailability extends Model
{
    use HasUuids;

    protected $table = 'activities_availabilities';

    protected $fillable = [
        'activity_id',
        'date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AvailabilityStatusEnum::class,
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
