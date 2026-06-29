<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scanner;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scanner\StoreScannerRequest;
use App\Http\Requests\Scanner\UpdateScannerRequest;
use App\Http\Resources\ScannerResource;
use App\Models\Scanner;
use App\Services\Scanner\ScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScannerController extends Controller
{
    public function __construct(
        private ScannerService $scannerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scanners = $this->scannerService->getUserScanners($request->user());

        return ScannerResource::collection($scanners)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreScannerRequest $request): JsonResponse
    {
        $scanner = $this->scannerService->create($request->user(), $request->validated());

        return (new ScannerResource($scanner))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateScannerRequest $request, Scanner $scanner): JsonResponse
    {
        $this->authorize('update', $scanner);

        $scanner = $this->scannerService->update($scanner, $request->validated());

        return (new ScannerResource($scanner))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Request $request, Scanner $scanner): JsonResponse
    {
        $this->authorize('delete', $scanner);

        $this->scannerService->delete($scanner);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
