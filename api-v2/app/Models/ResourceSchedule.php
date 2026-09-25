<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WeekDayEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plage de travail habituelle d'une ressource dans une activité. Un jour peut en avoir plusieurs.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $resource_id
 * @property string $activity_id
 * @property WeekDayEnum $weekday
 * @property string $start_time
 * @property string $end_time
 * @property-read AgendaResource|null $resource
 */
class ResourceSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'activity_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => WeekDayEnum::class,
        ];
    }

    /**
     * @return BelongsTo<AgendaResource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(AgendaResource::class);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
