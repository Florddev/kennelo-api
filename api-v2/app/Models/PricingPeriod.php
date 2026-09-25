<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Période tarifaire de l'entreprise, partagée par ses activités : chacune dit comment elle l'applique
 * (ActivityPeriodSetting). Sans dates, c'est la période de base : toute l'année, créée avec l'entreprise,
 * jamais datée ni supprimée. Une période récurrente revient chaque année : ses dates ne comptent qu'en mois
 * et jour, et peuvent enjamber le 31 décembre.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $is_recurring
 * @property int|null $priority
 * @property string|null $color
 * @property Carbon|null $created_at
 * @property-read ActivityPeriodSetting|null $activitySetting réglage d'une activité, posé par ActivityPricingService::periods()
 */
class PricingPeriod extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_recurring',
        'priority',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_recurring' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function isBase(): bool
    {
        return $this->start_date === null;
    }

    public function contains(CarbonInterface $date): bool
    {
        if ($this->start_date === null || $this->end_date === null) {
            return true;
        }

        if (! $this->is_recurring) {
            return $date->toDateString() >= $this->start_date->toDateString()
                && $date->toDateString() <= $this->end_date->toDateString();
        }

        $day = $date->format('m-d');
        $start = $this->start_date->format('m-d');
        $end = $this->end_date->format('m-d');

        return $start <= $end
            ? $day >= $start && $day <= $end
            : $day >= $start || $day <= $end;
    }

    /**
     * Nombre de jours couverts, bornes comprises : entre deux périodes qui se chevauchent, la plus courte l'emporte.
     */
    public function lengthInDays(): int
    {
        if ($this->start_date === null || $this->end_date === null) {
            return PHP_INT_MAX;
        }

        if (! $this->is_recurring) {
            return (int) $this->start_date->diffInDays($this->end_date) + 1;
        }

        // Mesurée sur une année bissextile, pour qu'un 29 février reste une date valable.
        $start = Carbon::create(2000, $this->start_date->month, $this->start_date->day);
        $end = Carbon::create(2000, $this->end_date->month, $this->end_date->day);

        if ($end->lessThan($start)) {
            $end->addYear();
        }

        return (int) $start->diffInDays($end) + 1;
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->whereNull('start_date');
    }

    public function scopeSeasonal(Builder $query): Builder
    {
        return $query->whereNotNull('start_date');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<ActivityPeriodSetting, $this>
     */
    public function activitySettings(): HasMany
    {
        return $this->hasMany(ActivityPeriodSetting::class);
    }
}
