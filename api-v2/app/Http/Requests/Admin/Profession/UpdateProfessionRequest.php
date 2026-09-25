<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Profession;

use App\Enums\BookingModeEnum;
use App\Models\Profession;
use Illuminate\Validation\Rule;

/**
 * Le code ne change pas : il sert de filtre dans la recherche. Ce que les activités du métier utilisent
 * déjà (mode de réservation, lieux, espèces) est protégé par le service.
 */
class UpdateProfessionRequest extends StoreProfessionRequest
{
    public function rules(): array
    {
        /** @var Profession $profession */
        $profession = $this->route('profession');
        $mode = $this->has('booking_mode')
            ? BookingModeEnum::tryFrom((string) $this->input('booking_mode'))
            : $profession->booking_mode;

        return [
            'profession_category_id' => ['sometimes', 'uuid', Rule::exists('profession_categories', 'id')],
            'code' => ['prohibited'],
            ...$this->translationRules('name', 'sometimes', 100),
            ...$this->translationRules('description', 'nullable', 2000),
            'booking_mode' => ['sometimes', Rule::enum(BookingModeEnum::class)],
            // Changer de mode sans changer d'unité laisserait une unité incompatible : elle est vérifiée dans les deux cas.
            'billing_unit' => [Rule::requiredIf($this->has('booking_mode')), $this->billingUnitRule($mode)],
            ...$this->profileRules('sometimes'),
        ];
    }
}
