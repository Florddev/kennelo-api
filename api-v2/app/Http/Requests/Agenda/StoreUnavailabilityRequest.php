<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use App\Enums\ResourceBookingKindEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Absence d'une personne ou blocage d'un équipement ou d'un espace, sur une plage qui n'est pas encore passée.
 * Les instants sont en ISO 8601, avec leur fuseau.
 */
class StoreUnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kind' => ['sometimes', Rule::enum(ResourceBookingKindEnum::class)->only(ResourceBookingKindEnum::unavailabilities())],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at', 'after:now'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{kind: string, starts_at: string, ends_at: string, note: string|null}
     */
    public function unavailability(): array
    {
        return [
            'kind' => (string) $this->validated('kind', ResourceBookingKindEnum::ABSENCE->value),
            'starts_at' => (string) $this->validated('starts_at'),
            'ends_at' => (string) $this->validated('ends_at'),
            'note' => $this->validated('note'),
        ];
    }
}
