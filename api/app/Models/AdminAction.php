<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminActionTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AdminActionTypeEnum $action
 * @property array<string, mixed>|null $metadata
 * @property-read User|null $admin
 * @property-read User|null $target
 */
class AdminAction extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'target_user_id',
        'action',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => AdminActionTypeEnum::class,
            'metadata' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
