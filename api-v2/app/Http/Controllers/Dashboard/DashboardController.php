<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Organization;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Dashboard
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboards) {}

    /**
     * Dashboard of an activity
     *
     * Pour ceux qui voient ses réservations (bookings.view). revenue vaut null sans finance.view.
     */
    public function activity(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('viewBookings', $activity);

        $withRevenue = $activity->organization !== null && $request->user()->can('viewFinance', $activity->organization);

        return response()->json(['data' => $this->dashboards->forActivity($activity, $withRevenue)]);
    }

    /**
     * Dashboard of an organization
     *
     * Toutes les activités de l'entreprise, pour ceux qui voient ses réservations sur toute l'entreprise.
     * revenue vaut null sans finance.view.
     */
    public function organization(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('viewDashboard', $organization);

        return response()->json(['data' => $this->dashboards->forOrganization($organization, $request->user()->can('viewFinance', $organization))]);
    }
}
