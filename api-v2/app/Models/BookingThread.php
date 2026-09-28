<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Fil d'une réservation dans la conversation de son client avec l'activité. Il est archivé quand la réservation
 * se termine ; ses messages restent lisibles.
 *
 * @property string $booking_id
 * @property string $conversation_id
 * @property Carbon|null $archived_at
 * @property-read Booking|null $booking
 */
class BookingThread extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'booking_id';

    protected $keyType = 'string';

    protected $fillable = [
        'booking_id',
        'conversation_id',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->archived_at === null;
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
}
