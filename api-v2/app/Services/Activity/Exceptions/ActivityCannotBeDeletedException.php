<?php

declare(strict_types=1);

namespace App\Services\Activity\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ActivityCannotBeDeletedException extends Exception
{
    public function __construct()
    {
        parent::__construct(__('activity.has_active_bookings'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
