<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scanner;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScannerScanResource;
use App\Services\Scanner\ScannerScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerScanController extends Controller
{
    public function __construct(
        private ScannerScanService $scannerScanService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scans = $this->scannerScanService->getUserScans($request->user());

        return ScannerScanResource::collection($scans)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
