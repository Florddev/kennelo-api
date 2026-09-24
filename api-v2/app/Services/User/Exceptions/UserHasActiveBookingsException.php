<?php

declare(strict_types=1);

namespace App\Services\User\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UserHasActiveBookingsException extends Exception
{
    public static function cannotDeleteAccount(): self
    {
        return new self(__('account.has_active_bookings'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
