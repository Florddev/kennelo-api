<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un rôle d'un membre, sur toute l'entreprise (activity_id NULL) ou sur une activité.
 * organization_id est répété pour les clés étrangères composites : un rôle ne peut viser
 * qu'une activité de la même entreprise.
 *
 * @property string $organization_id
 * @property string $organization_member_id
 * @property OrganizationRoleEnum $role
 * @property string|null $activity_id
 */
class OrganizationMemberRole extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'organization_member_id',
        'role',
        'activity_id',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRoleEnum::class,
        ];
    }

    /**
     * @return BelongsTo<OrganizationMember, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }
}
