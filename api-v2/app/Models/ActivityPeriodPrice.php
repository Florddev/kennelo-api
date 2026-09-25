<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WeekDayEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prix TTC d'une place pour une nuit (ou un jour) de la période, pour un jour de la semaine ou pour tous
 * (weekday NULL), avec le supplément par animal au-delà du premier dans la même place.
 *
 * @property string $id
 * @property string $activity_period_setting_id
 * @property string $activity_unit_type_id
 * @property WeekDayEnum|null $weekday
 * @property numeric-string $price
 * @property numeric-string|null $extra_animal_price
 */
class ActivityPeriodPrice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_unit_type_id',
        'weekday',
        'price',
        'extra_animal_price',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => WeekDayEnum::class,
            'price' => 'decimal:2',
            'extra_animal_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ActivityPeriodSetting, $this>
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(ActivityPeriodSetting::class, 'activity_period_setting_id');
    }

    /**
     * @return BelongsTo<ActivityUnitType, $this>
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(ActivityUnitType::class, 'activity_unit_type_id');
    }
}
