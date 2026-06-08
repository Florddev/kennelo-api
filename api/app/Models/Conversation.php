<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $user_id
 * @property string $activity_id
 * @property Carbon|null $last_message_at
 * @property-read User|null $user
 * @property-read Activity|null $activity
 * @property-read Message|null $latestMessage
 */
class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'activity_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function bookingThreads(): HasMany
    {
        return $this->hasMany(BookingThread::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latest('created_at');
    }
}
