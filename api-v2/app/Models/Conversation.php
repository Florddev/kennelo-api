<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Conversation entre un client et l'équipe d'une activité : une seule par couple. Côté pro, y ont accès les
 * membres qui ont messages.reply sur l'activité. Chaque réservation du client dans l'activité y a son fil.
 *
 * @property string $id
 * @property string $user_id
 * @property string $activity_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
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

    public function isClient(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Le client.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('created_at');
    }

    /**
     * @return HasManyThrough<MessageFile, Message, $this>
     */
    public function files(): HasManyThrough
    {
        return $this->hasManyThrough(MessageFile::class, Message::class);
    }

    /**
     * @return HasMany<BookingThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(BookingThread::class);
    }
}
