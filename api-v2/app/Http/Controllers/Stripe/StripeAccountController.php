<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Stripe\StripeConnectService;
use Illuminate\Http\JsonResponse;

/**
 * Compte Stripe Connect de l'entreprise, qui reçoit les versements des réservations.
 *
 * @tags Organizations
 */
class StripeAccountController extends Controller
{
    public function __construct(
        private readonly StripeConnectService $connect,
    ) {}

    /**
     * Open an onboarding session
     *
     * Retourne le secret client attendu par le composant d'inscription intégré de Stripe.
     */
    public function store(Organization $organization): JsonResponse
    {
        $this->authorize('manageBilling', $organization);

        return response()->json([
            'client_secret' => $this->connect->createAccountSession($organization),
        ]);
    }

    /**
     * Refresh the account status
     *
     * Relit l'état du compte chez Stripe et retourne l'entreprise à jour.
     */
    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('manageBilling', $organization);

        return new OrganizationResource($this->connect->refresh($organization)->load(['address', 'subscription.plan']));
    }
}
