<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use App\Models\Service;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un forfait ne contient que des prestations simples de la même entreprise, jamais un autre forfait.
 */
class ReplacePackageItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Service $package */
        $package = $this->route('service');

        return [
            'service_ids' => ['present', 'array', 'max:50'],
            'service_ids.*' => [
                'distinct',
                'uuid',
                // Closure : Rule::exists()->where() écrirait false en chaîne vide dans la règle.
                Rule::exists('services', 'id')->where(fn (Builder $query) => $query
                    ->where('organization_id', $package->organization_id)
                    ->where('is_package', false)
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'service_ids.*.exists' => __('catalog.invalid_package_item'),
        ];
    }
}
