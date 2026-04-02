<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $message_id
 * @property string $user_id
 *
 * Uses composite primary key (message_id, user_id) at DB level.
 * Eloquent does not support composite PKs — use query builder for direct lookups.
 */
class MessageRead extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
