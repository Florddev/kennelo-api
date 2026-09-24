<?php

declare(strict_types=1);

namespace App\Services\Organization\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class OrganizationCannotBeClosedException extends Exception
{
    public static function hasActiveBookings(): self
    {
        return new self(__('organization.has_active_bookings'));
    }

    public static function subscriptionStillActive(): self
    {
        return new self(__('organization.subscription_still_active'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
