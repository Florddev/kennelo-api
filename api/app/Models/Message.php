<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageType;
use App\Enums\SenderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $conversation_id
 * @property string|null $booking_id
 * @property string|null $sender_id
 * @property SenderType $sender_type
 * @property MessageType $message_type
 * @property string|null $content
 * @property-read Conversation $conversation
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
            'sender_type' => SenderType::class,
            'message_type' => MessageType::class,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MessageFile::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }
}
