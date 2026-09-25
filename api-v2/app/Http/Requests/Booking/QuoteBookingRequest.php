<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Enums\LocationModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Demande de séjour : les dates, une entrée par place occupée avec les animaux du client qui la partagent,
 * et les options de séjour choisies pour chaque animal. Un animal n'occupe qu'une place.
 *
 * Les règles qui dépendent du tarif de l'activité (jours fermés, séjour minimum, capacité, espèces d'une place)
 * sont contrôlées par le devis.
 */
class QuoteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;
        $petIds = collect((array) $this->input('units'))->pluck('pet_ids')->flatten()->filter(fn ($id): bool => is_string($id))->all();

        return [
            'activity_id' => ['required', 'uuid'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'location' => ['sometimes', Rule::enum(LocationModeEnum::class)->only([LocationModeEnum::AT_PRO, LocationModeEnum::AT_CLIENT])],
            'address_id' => [
                Rule::requiredIf($this->input('location') === LocationModeEnum::AT_CLIENT->value),
                'nullable',
                'uuid',
                Rule::exists('user_addresses', 'id')->where('user_id', $userId),
            ],
            'units' => ['required', 'array', 'min:1', 'max:10'],
            'units.*.unit_type_id' => ['required', 'uuid'],
            'units.*.pet_ids' => ['required', 'array', 'min:1', 'max:10'],
            'units.*.pet_ids.*' => ['distinct', 'uuid', Rule::exists('pets', 'id')->where('user_id', $userId)],
            'options' => ['sometimes', 'array', 'max:50'],
            'options.*.service_id' => ['required', 'uuid'],
            'options.*.pet_id' => ['required', 'uuid', Rule::in($petIds)],
            'options.*.quantity' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
