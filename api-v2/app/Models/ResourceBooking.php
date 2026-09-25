<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResourceBookingKindEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Occupation d'une ressource sur une plage [starts_at, ends_at) : deux plages bout à bout ne se chevauchent pas.
 * Rendez-vous, options placées, absences et blocages partagent la table ; sous PostgreSQL, une contrainte
 * d'exclusion refuse deux occupations qui se chevauchent. Annuler une prestation supprime sa ligne.
 *
 * Les instants sont en UTC.
 *
 * @property string $id
 * @property string $resource_id
 * @property string|null $booking_item_id
 * @property ResourceBookingKindEnum $kind
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $note
 * @property string|null $created_by
 * @property-read AgendaResource|null $resource
 * @property-read BookingItem|null $bookingItem
 */
class ResourceBooking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'resource_id',
        'booking_item_id',
        'kind',
        'starts_at',
        'ends_at',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ResourceBookingKindEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Occupations qui chevauchent la plage [$start, $end).
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query
            ->where('resource_bookings.starts_at', '<', $end->toImmutable()->utc())
            ->where('resource_bookings.ends_at', '>', $start->toImmutable()->utc());
    }

    /**
     * @return BelongsTo<AgendaResource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(AgendaResource::class);
    }

    /**
     * @return BelongsTo<BookingItem, $this>
     */
    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }
}
