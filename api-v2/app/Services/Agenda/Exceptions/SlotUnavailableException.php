<?php

declare(strict_types=1);

namespace App\Services\Agenda\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * La ressource est déjà occupée sur la plage demandée. L'application le vérifie avant d'écrire ; sous PostgreSQL,
 * la contrainte d'exclusion de resource_bookings tranche entre deux demandes simultanées (erreur 23P01).
 */
class SlotUnavailableException extends Exception
{
    /**
     * Code SQLSTATE d'une violation de contrainte d'exclusion (PostgreSQL).
     */
    public const string EXCLUSION_VIOLATION = '23P01';

    public static function taken(): self
    {
        return new self(__('agenda.slot_unavailable'));
    }

    public static function occupied(): self
    {
        return new self(__('agenda.occupied'));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
