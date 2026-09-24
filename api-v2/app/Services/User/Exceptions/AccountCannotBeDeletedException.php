<?php

declare(strict_types=1);

namespace App\Services\User\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AccountCannotBeDeletedException extends Exception
{
    public static function hasActiveBookings(): self
    {
        return new self(__('account.has_active_bookings'));
    }

    public static function ownsOrganization(): self
    {
        return new self(__('account.owns_organization'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
