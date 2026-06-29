<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ReviewerTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Carbon $check_in_date
 * @property Carbon $check_out_date
 * @property BookingStatusEnum $status
 * @property PaymentStatusEnum $payment_status
 * @property-read Activity|null $activity
 * @property-read User|null $user
 * @property-read Collection<int, Pet> $pets
 * @property-read Collection<int, Service> $services
 */
class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'activity_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'service_fee',
        'platform_fee',
        'activity_amount',
        'status',
        'special_requests',
        'stripe_payment_intent_id',
        'payment_status',
        'paid_at',
        'refunded_at',
        'stripe_charge_id',
        'stripe_transfer_group',
        'stripe_transfer_id',
        'stripe_refund_id',
        'refunded_amount',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'total_price' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'activity_amount' => 'decimal:2',
            'status' => BookingStatusEnum::class,
            'payment_status' => PaymentStatusEnum::class,
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refunded_amount' => 'decimal:2',
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

    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'booking_pets')
            ->using(BookingPet::class)
            ->as('booking_pet')
            ->withPivot(['price_per_night', 'number_of_nights', 'subtotal'])
            ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'booking_services')
            ->using(BookingServicePivot::class)
            ->as('booking_service')
            ->withPivot(['quantity', 'unit_price', 'subtotal'])
            ->withTimestamps();
    }

    public function bookingThread(): HasOne
    {
        return $this->hasOne(BookingThread::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function userReview(): HasOne
    {
        return $this->hasOne(Review::class)->where('reviewer_type', ReviewerTypeEnum::USER->value);
    }

    public function activityReview(): HasOne
    {
        return $this->hasOne(Review::class)->where('reviewer_type', ReviewerTypeEnum::ACTIVITY->value);
    }
}
