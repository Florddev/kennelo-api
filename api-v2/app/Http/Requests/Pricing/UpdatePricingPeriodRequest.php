<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use App\Models\PricingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * La période de base couvre toute l'année : seuls son nom et sa couleur changent.
 */
class UpdatePricingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var PricingPeriod|null $period */
        $period = $this->route('pricingPeriod');

        if ($period?->isBase() === true) {
            return [
                'name' => ['sometimes', 'string', 'max:100'],
                'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'start_date' => ['prohibited'],
                'end_date' => ['prohibited'],
                'is_recurring' => ['prohibited'],
                'priority' => ['prohibited'],
            ];
        }

        $isRecurring = $this->has('is_recurring') ? $this->boolean('is_recurring') : $period?->is_recurring;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => [
                'sometimes',
                'date_format:Y-m-d',
                Rule::when(! $isRecurring, 'after_or_equal:'.($this->input('start_date') ?? $period?->start_date?->toDateString())),
            ],
            'is_recurring' => ['sometimes', 'boolean'],
            ...StorePricingPeriodRequest::displayRules(),
        ];
    }
}
