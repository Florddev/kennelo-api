<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreTokenRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Auth
 */
class PersonalAccessTokenController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
    ) {}

    /**
     * Issue a personal access token
     *
     * Connexion des applications mobiles, sans session : le token s'envoie ensuite dans l'en-tête Authorization: Bearer.
     * Sans code alors que la double authentification est active, la réponse vaut { two_factor: true } ; avec un mot de
     * passe expiré et sans new_password, { password_expired: true }. On renvoie alors les mêmes identifiants avec le code
     * (code ou recovery_code) et, si besoin, new_password et new_password_confirmation.
     */
    public function store(StoreTokenRequest $request): JsonResponse
    {
        return $this->authentication->issueToken($request->authenticate(), $request->validated());
    }

    /**
     * Revoke the current token
     */
    public function destroy(Request $request): Response
    {
        $this->authentication->logout($request);

        return response()->noContent();
    }
}
