<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ActivityPermissionEnum $permission
 */
class ActivityRolePermission extends Model
{
    use HasUuids;

    protected $fillable = [
        'role_id',
        'permission',
    ];

    protected function casts(): array
    {
        return [
            'permission' => ActivityPermissionEnum::class,
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ActivityRole::class, 'role_id');
    }
}
