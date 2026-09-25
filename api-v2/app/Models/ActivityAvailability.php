<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AvailabilityStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Exception aux horaires habituels sur une journée entière : fermeture (congés), ou ouverture un jour
 * normalement fermé. Une activité a au plus une exception par date.
 *
 * @property string $id
 * @property string $activity_id
 * @property Carbon $date
 * @property AvailabilityStatusEnum $status
 * @property string|null $note
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

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
