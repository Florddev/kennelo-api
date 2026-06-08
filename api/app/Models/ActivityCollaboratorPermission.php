<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPermissionEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityCollaboratorPermission extends Model
{
    use HasUuids;

    protected $fillable = [
        'activity_id',
        'user_id',
        'permission',
    ];

    protected function casts(): array
    {
        return [
            'permission' => ActivityPermissionEnum::class,
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
}
