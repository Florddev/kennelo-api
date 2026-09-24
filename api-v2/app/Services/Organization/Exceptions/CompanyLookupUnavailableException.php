<?php

declare(strict_types=1);

namespace App\Services\Organization\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * L'API « Recherche d'entreprises » ne répond pas. L'exception est journalisée, et l'utilisateur
 * peut saisir ses informations à la main.
 */
class CompanyLookupUnavailableException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => __('organization.company_lookup_unavailable')], 503);
    }
}
