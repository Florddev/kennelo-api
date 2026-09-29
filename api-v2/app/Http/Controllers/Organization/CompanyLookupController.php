<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Services\Organization\CompanyLookupService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Organizations
 */
class CompanyLookupController extends Controller
{
    /**
     * Look up a company by SIREN
     *
     * Données du registre public, pour préremplir la création d'une entreprise.
     */
    public function __invoke(string $siren, CompanyLookupService $companies): JsonResponse
    {
        $company = $companies->findBySiren($siren);

        abort_if($company === null, 404, __('organization.company_not_found'));

        return response()->json($company);
    }
}
