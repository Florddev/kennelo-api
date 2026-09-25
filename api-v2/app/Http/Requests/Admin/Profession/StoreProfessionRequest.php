<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Profession;

use App\Enums\BillingUnitEnum;
use App\Enums\BookingModeEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\LocationModeEnum;
use App\Enums\PricingDimensionEnum;
use App\Http\Requests\Concerns\ValidatesTranslations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un métier : son mode de réservation et son unité de facturation, ses lieux autorisés, ses espèces,
 * ses critères de prix et les justificatifs qu'il exige (la liste remplace l'existante).
 */
class StoreProfessionRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profession_category_id' => ['required', 'uuid', Rule::exists('profession_categories', 'id')],
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:50', Rule::unique('professions', 'code')],
            ...$this->translationRules('name', 'required', 100),
            ...$this->translationRules('description', 'nullable', 2000),
            'booking_mode' => ['required', Rule::enum(BookingModeEnum::class)],
            'billing_unit' => ['required', $this->billingUnitRule(BookingModeEnum::tryFrom((string) $this->input('booking_mode')))],
            ...$this->profileRules('required'),
        ];
    }

    /**
     * Règles communes à la création et à la modification.
     *
     * @param  'required'|'sometimes'  $presence
     * @return array<string, array<int, mixed>>
     */
    protected function profileRules(string $presence): array
    {
        return [
            'locations' => [$presence, 'array', 'min:1'],
            'locations.*' => ['distinct', Rule::enum(LocationModeEnum::class)],
            'pricing_dimensions' => ['sometimes', 'array'],
            'pricing_dimensions.*' => ['distinct', Rule::enum(PricingDimensionEnum::class)],
            'animal_type_ids' => [$presence, 'array', 'min:1'],
            'animal_type_ids.*' => ['distinct', 'uuid', Rule::exists('animal_types', 'id')],
            'documents' => ['sometimes', 'array', 'max:20'],
            'documents.*.type' => ['required', 'distinct', Rule::enum(DocumentTypeEnum::class)],
            'documents.*.is_required' => ['sometimes', 'boolean'],
            'documents.*.validity_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * L'unité de facturation suit le mode de réservation : nuits ou jours pour un séjour, créneaux pour un rendez-vous.
     */
    protected function billingUnitRule(?BookingModeEnum $mode): mixed
    {
        return Rule::enum(BillingUnitEnum::class)->only($mode?->billingUnits() ?? BillingUnitEnum::cases());
    }
}
