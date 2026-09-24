<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class MagicLinkController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly MagicLinkService $magicLinkService,
    ) {}

    /**
     * Send a magic link
     *
     * @unauthenticated
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->magicLinkService->send((string) $request->string('email'));

        return response()->json([
            'message' => __('login.magic_link_sent'),
        ]);
    }

    /**
     * Verify a magic link
     *
     * @unauthenticated
     */
    public function verify(string $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        $used = $this->magicLinkService->markAsUsed(
            $user,
            (string) $request->query('expires'),
            (string) $request->query('signature'),
        );

        abort_unless($used, 410, __('login.magic_link_invalid'));

        // Un lien magique ne dispense jamais de la double authentification, même sur un appareil mémorisé.
        return $this->authentication->proceed($request, $user, trustRememberedDevice: false);
    }
}
