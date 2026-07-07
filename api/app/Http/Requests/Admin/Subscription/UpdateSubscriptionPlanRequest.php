<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'limits' => ['sometimes', 'array'],
            'limits.max_activities' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'limits.max_cycles_per_activity' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'limits.max_photos' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
