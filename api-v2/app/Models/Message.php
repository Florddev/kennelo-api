<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageSenderTypeEnum;
use App\Enums\MessageTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Message d'une conversation. Un message système n'a pas d'auteur : son contenu est le code de l'événement de
 * réservation (booking_confirmed…), traduit à la lecture dans la langue de chacun.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string|null $booking_id
 * @property string|null $sender_id
 * @property MessageSenderTypeEnum $sender_type
 * @property MessageTypeEnum $message_type
 * @property string|null $content
 * @property Carbon|null $created_at
 * @property-read Conversation|null $conversation
 * @property-read User|null $sender
 */
class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'conversation_id',
        'booking_id',
        'sender_id',
        'sender_type',
        'message_type',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'sender_type' => MessageSenderTypeEnum::class,
            'message_type' => MessageTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }

    /**
     * @return HasMany<MessageFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(MessageFile::class);
    }

    /**
     * @return HasMany<MessageRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }
}
