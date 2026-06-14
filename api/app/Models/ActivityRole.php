<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, ActivityRolePermission> $permissions
 * @property-read Collection<int, ActivityCollaborator> $collaborators
 */
class ActivityRole extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_id',
        'name',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(ActivityRolePermission::class, 'role_id');
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(ActivityCollaborator::class, 'role_id');
    }

    public function hasPermission(ActivityPermissionEnum $permission): bool
    {
        return $this->permissions()->where('permission', $permission->value)->exists();
    }
}
