<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\Pet;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'activity_id' => $this->activity_id,
            'check_in_date' => $this->check_in_date->toDateString(),
            'check_out_date' => $this->check_out_date->toDateString(),
            'total_price' => $this->total_price,
            'service_fee' => $this->service_fee,
            'platform_fee' => $this->platform_fee,
            'activity_amount' => $this->activity_amount,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'stripe_charge_id' => $this->stripe_charge_id,
            'stripe_transfer_group' => $this->stripe_transfer_group,
            'stripe_transfer_id' => $this->stripe_transfer_id,
            'stripe_refund_id' => $this->stripe_refund_id,
            'refunded_amount' => $this->refunded_amount,
            'refunded_at' => $this->refunded_at ? human_date($this->refunded_at) : null,
            'client_secret' => $this->client_secret ?? null,
            'checkout_url' => null,
            'special_requests' => $this->special_requests,
            'paid_at' => $this->paid_at ? human_date($this->paid_at) : null,
            'user' => new UserResource($this->whenLoaded('user')),
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'pets' => $this->whenLoaded('pets', fn () => $this->pets->map(fn (Pet $pet): array => [
                'id' => $pet->id,
                'name' => $pet->name,
                'price_per_night' => $pet->booking_pet->price_per_night,
                'number_of_nights' => $pet->booking_pet->number_of_nights,
                'subtotal' => $pet->booking_pet->subtotal,
                'animal_type' => $pet->relationLoaded('animalType') && $pet->animalType !== null
                    ? [
                        'id' => $pet->animalType->id,
                        'code' => $pet->animalType->code,
                        'name' => $pet->animalType->name,
                        'category' => $pet->animalType->category,
                    ]
                    : null,
            ])->values()->all()),
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'quantity' => $service->booking_service->quantity,
                'unit_price' => $service->booking_service->unit_price,
                'subtotal' => $service->booking_service->subtotal,
            ])),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
