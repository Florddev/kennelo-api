<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListSearchLogsRequest;
use App\Http\Resources\SearchLogResource;
use App\Services\Explore\SearchLogService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin Search Logs
 */
class SearchLogController extends Controller
{
    public function __construct(
        private SearchLogService $searchLogs
    ) {}

    public function index(ListSearchLogsRequest $request): JsonResponse
    {
        $logs = $this->searchLogs->paginate($request->validated());

        return SearchLogResource::collection($logs)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
