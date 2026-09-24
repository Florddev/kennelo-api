<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Forgot password
     *
     * @unauthenticated
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // Réponse identique que le compte existe, n'existe pas ou soit limité en fréquence :
        // sinon cet endpoint permettrait de savoir quelles adresses ont un compte.
        return response()->json(['status' => __(Password::RESET_LINK_SENT)]);
    }
}
