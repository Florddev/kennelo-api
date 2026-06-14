<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use App\Enums\CollaboratorStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CollaboratorStatusEnum $status
 * @property string|null $role_id
 */
class ActivityCollaborator extends Model
{
    protected $table = 'activity_collaborators';

    public $incrementing = false;

    protected $fillable = [
        'activity_id',
        'user_id',
        'status',
        'role_id',
        'invited_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CollaboratorStatusEnum::class,
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ActivityRole::class, 'role_id');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', CollaboratorStatusEnum::ACCEPTED->value);
    }

    public function scopeWithPermission(Builder $query, ActivityPermissionEnum $permission): Builder
    {
        return $query
            ->whereNotNull('role_id')
            ->whereHas('role.permissions', fn (Builder $q): Builder => $q->where('permission', $permission->value));
    }
}
