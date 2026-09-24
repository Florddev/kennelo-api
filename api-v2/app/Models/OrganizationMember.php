<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationMemberStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre de l'équipe d'une entreprise. Modèle minimal pour l'instant : la logique métier
 * (invitations, rôles par activité) arrive avec le lot « Entreprise et équipe ».
 */
class OrganizationMember extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'invited_by',
        'invited_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationMemberStatusEnum::class,
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<OrganizationMember>  $query
     * @return Builder<OrganizationMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrganizationMemberStatusEnum::ACTIVE);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
