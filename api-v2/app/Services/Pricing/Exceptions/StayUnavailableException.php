<?php

declare(strict_types=1);

namespace App\Services\Pricing\Exceptions;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Le séjour demandé ne peut pas être vendu : un jour fermé, une place sans prix ou un séjour trop court.
 */
class StayUnavailableException extends Exception
{
    public static function closed(CarbonInterface $date): self
    {
        return new self(__('booking.closed_on', ['date' => $date->toDateString()]));
    }

    public static function unpriced(CarbonInterface $date): self
    {
        return new self(__('booking.unpriced_on', ['date' => $date->toDateString()]));
    }

    public static function belowMinStay(int $minStay): self
    {
        return new self(__('booking.min_stay', ['count' => $minStay]));
    }

    public static function full(CarbonInterface $date): self
    {
        return new self(__('booking.full_on', ['date' => $date->toDateString()]));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
