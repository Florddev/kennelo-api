<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\BookingModeEnum;
use App\Enums\LocationModeEnum;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Demande de réservation, selon le mode du métier de l'activité.
 *
 * - Séjour : les dates, une entrée par place occupée avec les animaux du client qui la partagent, et les options
 *   de séjour choisies pour chaque animal. Un animal n'occupe qu'une place.
 * - Rendez-vous : la prestation, les animaux dans l'ordre où ils passent, le début (ISO 8601, avec son fuseau) et,
 *   au choix, la ressource (sinon la première libre).
 *
 * Les règles qui dépendent du tarif ou de l'agenda de l'activité (jours fermés, séjour minimum, capacité, espèces,
 * créneau libre) sont contrôlées par le devis.
 */
class QuoteBookingRequest extends FormRequest
{
    private ?BookingModeEnum $bookingMode = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;
        $isAppointment = $this->bookingMode() === BookingModeEnum::APPOINTMENT;

        return [
            'activity_id' => ['required', 'uuid'],
            'location' => ['sometimes', $isAppointment
                ? Rule::enum(LocationModeEnum::class)
                : Rule::enum(LocationModeEnum::class)->only([LocationModeEnum::AT_PRO, LocationModeEnum::AT_CLIENT])],
            'address_id' => [
                Rule::requiredIf($this->input('location') === LocationModeEnum::AT_CLIENT->value),
                'nullable',
                'uuid',
                Rule::exists('user_addresses', 'id')->where('user_id', $userId),
            ],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            ...($isAppointment ? $this->appointmentRules($userId) : $this->stayRules($userId)),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function stayRules(?string $userId): array
    {
        $petIds = collect((array) $this->input('units'))->pluck('pet_ids')->flatten()->filter(fn ($id): bool => is_string($id))->all();

        return [
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'units' => ['required', 'array', 'min:1', 'max:10'],
            'units.*.unit_type_id' => ['required', 'uuid'],
            'units.*.pet_ids' => ['required', 'array', 'min:1', 'max:10'],
            'units.*.pet_ids.*' => ['distinct', 'uuid', Rule::exists('pets', 'id')->where('user_id', $userId)],
            'options' => ['sometimes', 'array', 'max:50'],
            'options.*.service_id' => ['required', 'uuid'],
            'options.*.pet_id' => ['required', 'uuid', Rule::in($petIds)],
            'options.*.quantity' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function appointmentRules(?string $userId): array
    {
        return [
            'service_id' => ['required', 'uuid'],
            'pet_ids' => ['required', 'array', 'min:1', 'max:'.config('agenda.max_pets_per_appointment')],
            'pet_ids.*' => ['distinct', 'uuid', Rule::exists('pets', 'id')->where('user_id', $userId)],
            'starts_at' => ['required', 'date', 'after:now'],
            'resource_id' => ['sometimes', 'nullable', 'uuid'],
        ];
    }

    /**
     * Mode du métier de l'activité demandée ; un séjour si elle n'existe pas (le devis la refusera).
     */
    private function bookingMode(): BookingModeEnum
    {
        $activityId = $this->input('activity_id');

        return $this->bookingMode ??= (is_string($activityId) && Str::isUuid($activityId)
            ? Activity::query()->with('profession')->find($activityId)?->profession?->booking_mode
            : null) ?? BookingModeEnum::STAY;
    }
}
