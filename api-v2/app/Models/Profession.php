<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingUnitEnum;
use App\Enums\BookingModeEnum;
use App\Enums\LocationModeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Un métier, et les règles que l'application lit pour s'adapter : mode de réservation, unité de facturation,
 * lieux autorisés, espèces concernées, justificatifs exigés. Géré par Kennelo dans le back-office.
 *
 * @property string $id
 * @property string $profession_category_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property BookingModeEnum $booking_mode
 * @property BillingUnitEnum $billing_unit
 * @property bool $allows_at_pro
 * @property bool $allows_at_client
 * @property bool $allows_remote
 * @property list<string>|null $pricing_dimensions
 * @property bool $is_active
 * @property int $sort_order
 * @property-read ProfessionCategory|null $category
 */
class Profession extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'profession_category_id',
        'code',
        'name',
        'description',
        'booking_mode',
        'billing_unit',
        'allows_at_pro',
        'allows_at_client',
        'allows_remote',
        'pricing_dimensions',
        'is_active',
        'sort_order',
    ];

    public array $translatable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'booking_mode' => BookingModeEnum::class,
            'billing_unit' => BillingUnitEnum::class,
            'allows_at_pro' => 'boolean',
            'allows_at_client' => 'boolean',
            'allows_remote' => 'boolean',
            'pricing_dimensions' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function allowsLocation(LocationModeEnum $location): bool
    {
        return match ($location) {
            LocationModeEnum::AT_PRO => $this->allows_at_pro,
            LocationModeEnum::AT_CLIENT => $this->allows_at_client,
            LocationModeEnum::REMOTE => $this->allows_remote,
        };
    }

    /**
     * @return list<LocationModeEnum>
     */
    public function allowedLocations(): array
    {
        return array_values(array_filter(LocationModeEnum::cases(), $this->allowsLocation(...)));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<ProfessionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class, 'profession_category_id');
    }

    /**
     * @return BelongsToMany<AnimalType, $this>
     */
    public function animalTypes(): BelongsToMany
    {
        return $this->belongsToMany(AnimalType::class, 'profession_animal_types');
    }

    /**
     * @return HasMany<ProfessionDocumentRequirement, $this>
     */
    public function documentRequirements(): HasMany
    {
        return $this->hasMany(ProfessionDocumentRequirement::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
