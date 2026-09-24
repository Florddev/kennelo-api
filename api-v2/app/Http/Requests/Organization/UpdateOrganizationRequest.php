<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationLegalFormEnum;
use App\Enums\VatRegimeEnum;
use App\Http\Requests\Concerns\ValidatesAddress;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Mise à jour partielle. La forme juridique retenue est celle envoyée, sinon l'actuelle :
 * passer de particulier à société exige un SIREN.
 */
class UpdateOrganizationRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization = $this->organization();
        $hasSiren = $this->legalForm()->hasSiren();
        $individual = Rule::excludeIf(! $hasSiren);

        return [
            'legal_name' => ['sometimes', 'string', 'max:255'],
            'legal_form' => ['sometimes', Rule::enum(OrganizationLegalFormEnum::class)],
            'siren' => [
                $individual,
                $hasSiren && blank($organization->siren) ? 'required' : 'sometimes',
                'digits:9',
                Rule::unique('organizations', 'siren')->ignore($organization->id)->withoutTrashed(),
            ],
            'siret' => [$individual, 'sometimes', 'nullable', 'digits:14'],
            'ape_code' => [$individual, 'sometimes', 'nullable', 'string', 'max:6'],
            'vat_number' => [$individual, 'sometimes', 'nullable', 'string', 'max:20'],
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
                $siren = $this->input('siren', $this->organization()->siren);

                if ($validator->errors()->hasAny(['siren', 'siret']) || blank($siret) || ! $this->legalForm()->hasSiren()) {
                    return;
                }

                if (! str_starts_with((string) $siret, (string) $siren)) {
                    $validator->errors()->add('siret', __('organization.siret_mismatch'));
                }
            },
        ];
    }

    private function organization(): Organization
    {
        /** @var Organization */
        return $this->route('organization');
    }

    private function legalForm(): OrganizationLegalFormEnum
    {
        return OrganizationLegalFormEnum::tryFrom((string) $this->input('legal_form')) ?? $this->organization()->legal_form;
    }
}
