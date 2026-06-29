<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $weekday
 */
class ActivityCycleSettingPrice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'activities_cycles_settings_prices';

    protected $fillable = [
        'activity_cycle_setting_id',
        'weekday',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(ActivityCycleSetting::class, 'activity_cycle_setting_id');
    }
}
