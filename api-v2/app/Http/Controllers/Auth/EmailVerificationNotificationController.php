<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Resend verification email
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('account.email_already_verified')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => __('account.verification_link_sent')]);
    }
}
