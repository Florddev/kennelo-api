<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingRefund;
use App\Models\BookingUnit;
use App\Models\Pet;
use App\Services\MediaService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une réservation, vue par son client ou par l'équipe de l'activité.
 *
 * Le client voit ce qu'il paie ; l'équipe voit en plus la commission, ce qui lui sera versé et le client.
 * L'équipe ne voit l'adresse exacte d'un séjour chez le client qu'une fois le paiement capturé : avant,
 * seulement la ville. Un rendez-vous porte en plus son début et sa fin ; chacune de ses lignes, son horaire
 * et sa ressource.
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isClient = $request->user()?->id === $this->user_id;
        $isCaptured = $this->relationLoaded('payments') && $this->payments->contains(
            fn (BookingPayment $payment): bool => $payment->kind === PaymentKindEnum::INITIAL && $payment->status === PaymentStatusEnum::SUCCEEDED,
        );
        $refunds = $this->relationLoaded('payments') ? $this->payments->flatMap->refunds : collect();
        $isAppointment = $this->relationLoaded('activity') && $this->relationLoaded('items') && $this->isAppointment();

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'activity' => $this->whenLoaded('activity', fn (): array => [
                'id' => $this->activity?->id,
                'name' => $this->activity?->name,
                'timezone' => $this->activity?->timezone,
                'booking_mode' => $this->activity?->relationLoaded('profession') ? $this->activity->profession?->booking_mode->value : null,
                'image' => $this->activity?->relationLoaded('media')
                    ? $this->activity->getFirstMediaUrl(MediaService::COLLECTION_IMAGES) ?: null
                    : null,
            ]),
            'organization' => $this->whenLoaded('organization', fn (): array => [
                'id' => $this->organization?->id,
                'legal_name' => $this->organization?->legal_name,
            ]),
            'client' => $this->when(! $isClient && $this->relationLoaded('user'), fn (): array => [
                'id' => $this->user?->id,
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
            ]),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'starts_at' => $this->when($isAppointment, fn (): string => $this->startsAt()->toISOString()),
            'ends_at' => $this->when($isAppointment, fn (): string => $this->endsAt()->toISOString()),
            'location_mode' => $this->location_mode->value,
            'service_address' => $this->whenLoaded('serviceAddress', fn () => $this->serviceAddress === null ? null : ($isClient || $isCaptured
                ? AddressResource::make($this->serviceAddress)
                : [
                    'postal_code' => $this->serviceAddress->postal_code,
                    'city' => $this->serviceAddress->city,
                    'department' => $this->serviceAddress->department,
                    'country' => $this->serviceAddress->country,
                ])),
            'special_requests' => $this->special_requests,
            'currency' => $this->currency,
            'items_amount' => $this->itemsAmount(),
            'travel_fee' => $this->travel_fee,
            'service_fee' => $this->service_fee,
            'total_price' => $this->total_price,
            'refunded_amount' => Money::sum('0', ...$refunds->map(fn (BookingRefund $refund): string => $refund->amount)->all()),
            'platform_fee' => $this->when(! $isClient, $this->platform_fee),
            'activity_amount' => $this->when(! $isClient, $this->activity_amount),
            'vat_rate' => $this->when(! $isClient, $this->vat_rate),
            'cancellation_policy' => $this->cancellation_policy->value,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancelled_by_role' => $this->cancelled_by_role?->value,
            'units' => $this->whenLoaded('units', fn (): array => $this->units->map(fn (BookingUnit $unit): array => [
                'id' => $unit->id,
                'unit_type' => ['id' => $unit->activity_unit_type_id, 'name' => $unit->unitType?->name],
                'nights' => $unit->nights,
                'subtotal' => $unit->subtotal,
                'price_breakdown' => $unit->price_breakdown,
                'pet_ids' => $this->relationLoaded('pets')
                    ? $this->pets->filter(fn (Pet $pet): bool => $pet->placement->booking_unit_id === $unit->id)->modelKeys()
                    : [],
            ])->all()),
            'pets' => $this->whenLoaded('pets', fn (): array => $this->pets->map(fn (Pet $pet): array => [
                'id' => $pet->id,
                'name' => $pet->name,
                'animal_type' => AnimalTypeResource::make($pet->animalType),
            ])->all()),
            'items' => $this->whenLoaded('items', fn (): array => $this->items->map(fn (BookingItem $item): array => [
                'id' => $item->id,
                'service' => ['id' => $item->service_id, 'name' => $item->service?->name],
                'pet_id' => $item->pet_id,
                'status' => $item->status->value,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'duration_minutes' => $item->duration_minutes,
                'starts_at' => $item->starts_at?->toISOString(),
                'ends_at' => $item->ends_at?->toISOString(),
                'resource' => $item->relationLoaded('resourceBooking') && $item->resourceBooking?->resource !== null
                    ? ['id' => $item->resourceBooking->resource->id, 'name' => $item->resourceBooking->resource->name]
                    : null,
                'payment_id' => $item->booking_payment_id,
            ])->all()),
            'payments' => $this->whenLoaded('payments', fn (): array => $this->payments->map(fn (BookingPayment $payment): array => [
                'id' => $payment->id,
                'kind' => $payment->kind->value,
                'amount' => $payment->amount,
                // Part des frais Kennelo dans le montant.
                'service_fee' => $payment->service_fee,
                'status' => $payment->status->value,
                'paid_at' => $payment->paid_at?->toISOString(),
                'refunded_amount' => bcsub($payment->amount, $payment->refundableAmount(), 2),
            ])->all()),
            // Présent quand le client doit confirmer le paiement initial (3-D Secure).
            'client_secret' => $this->when(
                $isClient && array_key_exists('client_secret', $this->resource->getAttributes()),
                fn (): ?string => $this->resource->getAttribute('client_secret'),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
