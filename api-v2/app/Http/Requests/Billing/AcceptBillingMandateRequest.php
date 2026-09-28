<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le mandat engage l'entreprise : il faut l'accepter explicitement (accepted: true).
 */
class AcceptBillingMandateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accepted' => ['required', 'accepted'],
        ];
    }
}
