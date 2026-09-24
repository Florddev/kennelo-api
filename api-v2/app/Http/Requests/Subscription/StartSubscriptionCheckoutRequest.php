<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartSubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_slug' => ['required', 'string', Rule::exists('subscription_plans', 'slug')->where('is_active', true)],
        ];
    }
}
