<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Billing;

use App\Http\Requests\Billing\ListInvoicesRequest as BaseListInvoicesRequest;

/**
 * organization_id garde les factures émises ou reçues par l'entreprise ; issuer, celles de Kennelo (kennelo) ou
 * émises au nom des entreprises (organization) ; search cherche dans le numéro.
 */
class ListInvoicesRequest extends BaseListInvoicesRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'organization_id' => ['sometimes', 'uuid'],
            'issuer' => ['sometimes', 'in:kennelo,organization'],
            'search' => ['sometimes', 'string', 'max:30'],
        ];
    }
}
