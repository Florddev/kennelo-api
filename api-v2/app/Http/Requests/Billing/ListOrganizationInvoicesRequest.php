<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

/**
 * direction : issued pour les factures de ses réservations, received pour ses récapitulatifs de commission ;
 * les deux sans filtre.
 */
class ListOrganizationInvoicesRequest extends ListInvoicesRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'direction' => ['sometimes', 'in:issued,received'],
        ];
    }
}
