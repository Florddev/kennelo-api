<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResourceTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ce qui se réserve dans l'agenda : une personne de l'équipe, un équipement ou un espace. Elle appartient à
 * l'entreprise, pas à une activité : une personne qui travaille dans deux activités n'est jamais réservée deux
 * fois au même moment. Elle est présente dans une activité dès qu'elle y a un planning, et y réalise alors toutes
 * les prestations sur rendez-vous.
 *
 * Une personne est liée à son membre d'équipe ; quand il quitte l'équipe, sa ressource reste désactivée pour
 * l'historique de ses rendez-vous, sans lien (organization_member_id vide).
 *
 * @property string $id
 * @property string $organization_id
 * @property ResourceTypeEnum $type
 * @property string|null $organization_member_id
 * @property string $name
 * @property bool $is_active
 * @property-read Organization|null $organization
 * @property-read OrganizationMember|null $member
 */
class AgendaResource extends Model
{
    use HasFactory, HasUuids;

    // Pas « Resource » : c'est un pseudo-type de PHP, que Pint écrit en minuscules dans les PHPDoc.
    protected $table = 'resources';

    protected $fillable = [
        'type',
        'organization_member_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ResourceTypeEnum::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ressources qui ont un planning dans l'activité.
     */
    public function scopePresentIn(Builder $query, Activity $activity): Builder
    {
        return $query->whereHas('schedules', fn (Builder $schedules) => $schedules->where('activity_id', $activity->id));
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<OrganizationMember, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }

    /**
     * @return HasMany<ResourceSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ResourceSchedule::class, 'resource_id')->orderBy('weekday')->orderBy('start_time');
    }

    /**
     * Rendez-vous, options placées, absences et blocages.
     *
     * @return HasMany<ResourceBooking, $this>
     */
    public function resourceBookings(): HasMany
    {
        return $this->hasMany(ResourceBooking::class, 'resource_id');
    }
}
