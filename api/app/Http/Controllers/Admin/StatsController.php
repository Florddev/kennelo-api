<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Services\Admin\Stats\StatsService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin Stats
 */
class StatsController extends Controller
{
    public function __construct(
        private StatsService $stats
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->overview(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function searches(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->searches(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function business(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->business(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function finance(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->finance(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function bookings(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->bookings(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function community(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->community(),
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
