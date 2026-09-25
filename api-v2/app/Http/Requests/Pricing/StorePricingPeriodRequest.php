<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une période saisonnière. Récurrente, seuls le mois et le jour de ses dates comptent : elle peut enjamber
 * le 31 décembre (du 20/12 au 05/01).
 */
class StorePricingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', Rule::when(! $this->boolean('is_recurring'), 'after_or_equal:start_date')],
            'is_recurring' => ['sometimes', 'boolean'],
            ...self::displayRules(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function displayRules(): array
    {
        return [
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
