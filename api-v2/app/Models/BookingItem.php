<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingItemStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Prestation vendue dans une réservation, pour un animal. Un rendez-vous a son horaire dès la réservation ;
 * une option de séjour est « à placer » : le pro la place ensuite dans son agenda. booking_payment_id désigne
 * le paiement qui l'a couverte.
 *
 * @property string $id
 * @property string $booking_id
 * @property string $service_id
 * @property string|null $pet_id
 * @property BookingItemStatusEnum $status
 * @property int $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $subtotal
 * @property int|null $duration_minutes
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $booking_payment_id
 * @property-read Booking|null $booking
 * @property-read Service|null $service
 * @property-read Pet|null $pet
 * @property-read BookingPayment|null $payment
 * @property-read ResourceBooking|null $resourceBooking
 */
class BookingItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'service_id',
        'pet_id',
        'status',
        'quantity',
        'unit_price',
        'subtotal',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'booking_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingItemStatusEnum::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'duration_minutes' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Une prestation retirée du catalogue reste lisible dans les réservations passées.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Pet, $this>
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * @return BelongsTo<BookingPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }

    /**
     * Sa place dans l'agenda, une fois placée.
     *
     * @return HasOne<ResourceBooking, $this>
     */
    public function resourceBooking(): HasOne
    {
        return $this->hasOne(ResourceBooking::class);
    }
}
