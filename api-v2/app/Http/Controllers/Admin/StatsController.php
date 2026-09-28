<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Stats\StatsService;
use Illuminate\Http\JsonResponse;

/**
 * Indicateurs du back-office, recalculés au plus toutes les deux minutes.
 *
 * @tags Admin Stats
 */
class StatsController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    /**
     * Platform overview
     *
     * Entreprises et activités par statut, activités réservables, comptes, réservations, signalements d'avis en
     * attente.
     */
    public function overview(): JsonResponse
    {
        return response()->json(['data' => $this->stats->overview()]);
    }

    /**
     * Finance KPIs
     *
     * Réservations encaissées : volume payé par les clients, part de Kennelo, part des entreprises,
     * remboursements, panier moyen, volume par mois.
     */
    public function finance(): JsonResponse
    {
        return response()->json(['data' => $this->stats->finance()]);
    }

    /**
     * Booking KPIs
     */
    public function bookings(): JsonResponse
    {
        return response()->json(['data' => $this->stats->bookings()]);
    }

    /**
     * Community KPIs
     *
     * Inscriptions par mois, comptes actifs, professionnels, avis et messagerie.
     */
    public function community(): JsonResponse
    {
        return response()->json(['data' => $this->stats->community()]);
    }
}
