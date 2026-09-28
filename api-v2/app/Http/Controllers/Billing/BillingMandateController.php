<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\AcceptBillingMandateRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;

/**
 * @tags Billing
 */
class BillingMandateController extends Controller
{
    /**
     * Accept the billing mandate
     *
     * L'entreprise autorise Kennelo à émettre les factures de ses réservations en son nom et pour son compte.
     * Sans ce mandat, ses activités ne sont pas réservables. Droit billing.manage ; une acceptation déjà donnée
     * garde sa date.
     */
    public function store(AcceptBillingMandateRequest $request, Organization $organization, OrganizationService $organizations): OrganizationResource
    {
        $this->authorize('manageBilling', $organization);

        return new OrganizationResource($organizations->acceptBillingMandate($organization, $request->user()));
    }
}
