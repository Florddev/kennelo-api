<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Activity;
use App\Models\Service;
use App\Services\Service\ServiceService;
use Illuminate\Http\JsonResponse;

class ActivityServiceController extends Controller
{
    public function __construct(
        private ServiceService $service
    ) {}

    public function index(Activity $activity): JsonResponse
    {
        $this->authorize('manageServices', $activity);

        $services = $this->service->list($activity);

        return ServiceResource::collection($services)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreServiceRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageServices', $activity);

        $service = $this->service->create($activity, $request->validated());

        return (new ServiceResource($service))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Activity $activity, Service $service): JsonResponse
    {
        $this->authorize('manageServices', $activity);
        abort_if((string) $service->activity_id !== (string) $activity->id, 404);

        $service = $this->service->update($service, $request->validated());

        return (new ServiceResource($service))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity, Service $service): JsonResponse
    {
        $this->authorize('manageServices', $activity);
        abort_if((string) $service->activity_id !== (string) $activity->id, 404);

        $this->service->delete($service);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
