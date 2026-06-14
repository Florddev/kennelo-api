<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CollaboratorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CollaboratorStatusEnum $status
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
}
