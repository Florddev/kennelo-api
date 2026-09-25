<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prestation du catalogue de l'entreprise : ce qui est vendu, indépendamment de l'activité qui la vend.
 * Ses prix sont dans service_prices, ses conditions de vente dans activity_services.
 * Une prestation déjà vendue ne se supprime pas : la suppression est logique.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_package
 * @property bool $requires_scheduling
 * @property bool $is_active
 * @property-read ActivityOffer $offer
 */
class Service extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_package',
        'requires_scheduling',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_package' => 'boolean',
            'requires_scheduling' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Grille rangée par espèce, puis de la ligne la plus générale à la plus précise.
     *
     * @return HasMany<ServicePrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class)
            ->orderBy('animal_type_id')
            ->orderByRaw('case when animal_breed_id is not null then 3 when coat_type is not null then 2 when size_class is not null then 1 else 0 end')
            ->orderBy('size_class')
            ->orderBy('id');
    }

    /**
     * Prestations comprises dans ce forfait.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function packageItems(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'service_package_items', 'package_id', 'service_id');
    }

    /**
     * Forfaits qui comprennent cette prestation.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'service_package_items', 'service_id', 'package_id');
    }

    /**
     * @return BelongsToMany<Activity, $this, ActivityOffer, 'offer'>
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_services')
            ->using(ActivityOffer::class)
            ->as('offer')
            ->withPivot(['organization_id', 'offered_as', 'adjustment_percent', 'is_included', 'is_active'])
            ->withTimestamps();
    }
}
