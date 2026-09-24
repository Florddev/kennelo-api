<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationMemberStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Membre de l'équipe d'une entreprise. Le propriétaire a aussi sa ligne, active et sans rôle.
 *
 * @property string $organization_id
 * @property string $user_id
 * @property OrganizationMemberStatusEnum $status
 * @property Carbon|null $invited_at
 * @property Carbon|null $responded_at
 */
class OrganizationMember extends Model
{
    use HasFactory, HasUuids;

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

    public function isPending(): bool
    {
        return $this->status === OrganizationMemberStatusEnum::PENDING;
    }

    public function isOwner(): bool
    {
        return $this->organization->owner_id === $this->user_id;
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
     * @param  Builder<OrganizationMember>  $query
     * @return Builder<OrganizationMember>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrganizationMemberStatusEnum::PENDING);
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

    /**
     * @return HasMany<OrganizationMemberRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(OrganizationMemberRole::class);
    }
}
