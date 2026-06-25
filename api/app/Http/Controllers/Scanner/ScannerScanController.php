<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scanner;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scanner\ListScannerScansRequest;
use App\Http\Resources\ScannerScanResource;
use App\Services\Scanner\ScannerScanService;
use Illuminate\Http\JsonResponse;

class ScannerScanController extends Controller
{
    public function __construct(
        private ScannerScanService $scannerScanService
    ) {}

    public function index(ListScannerScansRequest $request): JsonResponse
    {
        $scans = $this->scannerScanService->getUserScans($request->user(), $request->validated());

        return ScannerScanResource::collection($scans)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
