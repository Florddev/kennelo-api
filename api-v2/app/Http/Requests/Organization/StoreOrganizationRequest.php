<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationLegalFormEnum;
use App\Enums\VatRegimeEnum;
use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Un particulier n'a ni SIREN, ni code APE, ni TVA : ces champs sont ignorés pour lui.
 */
class StoreOrganizationRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $individual = 'exclude_if:legal_form,'.OrganizationLegalFormEnum::INDIVIDUAL->value;

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'legal_form' => ['required', Rule::enum(OrganizationLegalFormEnum::class)],
            'siren' => [$individual, 'required', 'digits:9', Rule::unique('organizations', 'siren')->withoutTrashed()],
            'siret' => [$individual, 'nullable', 'digits:14'],
            'ape_code' => [$individual, 'nullable', 'string', 'max:6'],
            'vat_number' => [$individual, 'nullable', 'string', 'max:20'],
            'vat_regime' => [$individual, 'sometimes', Rule::enum(VatRegimeEnum::class)],
            ...$this->addressRules(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $siret = $this->input('siret');

                if ($validator->errors()->hasAny(['siren', 'siret']) || blank($siret)) {
                    return;
                }

                if (! str_starts_with((string) $siret, (string) $this->input('siren'))) {
                    $validator->errors()->add('siret', __('organization.siret_mismatch'));
                }
            },
        ];
    }
}
