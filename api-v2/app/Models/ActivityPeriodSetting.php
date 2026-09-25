<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WeekDayEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comment une activité applique une période tarifaire de son entreprise : séjour minimum, jours fermés,
 * majoration du tarif de base quand aucun prix n'est saisi, et sa grille (ActivityPeriodPrice).
 * Une activité sans réglage actif pour une période ne l'applique pas.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $activity_id
 * @property string $pricing_period_id
 * @property bool $is_active
 * @property int|null $min_stay
 * @property numeric-string|null $price_modifier_percent
 * @property int $closed_weekdays
 * @property-read PricingPeriod|null $pricingPeriod
 */
class ActivityPeriodSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'is_active',
        'min_stay',
        'price_modifier_percent',
        'closed_weekdays',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_stay' => 'integer',
            'price_modifier_percent' => 'decimal:2',
            'closed_weekdays' => 'integer',
        ];
    }

    public function isClosedOn(WeekDayEnum $day): bool
    {
        return WeekDayEnum::contains($this->closed_weekdays, $day);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return BelongsTo<PricingPeriod, $this>
     */
    public function pricingPeriod(): BelongsTo
    {
        return $this->belongsTo(PricingPeriod::class);
    }

    /**
     * @return HasMany<ActivityPeriodPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ActivityPeriodPrice::class)->orderBy('activity_unit_type_id')->orderBy('weekday');
    }
}
