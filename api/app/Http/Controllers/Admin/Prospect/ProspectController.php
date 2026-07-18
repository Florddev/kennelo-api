<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Prospect;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prospect\AssignProspectRequest;
use App\Http\Requests\Admin\Prospect\ImportProspectsRequest;
use App\Http\Requests\Admin\Prospect\ListProspectsRequest;
use App\Http\Requests\Admin\Prospect\UpdateProspectStatusRequest;
use App\Http\Resources\ProspectImportResource;
use App\Http\Resources\ProspectResource;
use App\Models\Prospect;
use App\Models\ProspectImport;
use App\Services\Admin\Prospect\ProspectService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin Prospects
 */
class ProspectController extends Controller
{
    public function __construct(
        private ProspectService $prospects
    ) {}

    public function index(ListProspectsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);

        $prospects = $this->prospects->paginate($request->validated());

        return ProspectResource::collection($prospects)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Prospect $prospect): JsonResponse
    {
        $this->authorize('view', $prospect);

        $prospect = $this->prospects->find($prospect->id);

        return (new ProspectResource($prospect))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function updateStatus(UpdateProspectStatusRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('update', $prospect);

        $prospect = $this->prospects->updateStatus($prospect, $request->validated());

        return $this->respond($prospect, 'Prospect status updated successfully');
    }

    public function assign(AssignProspectRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('update', $prospect);

        $prospect = $this->prospects->assign($prospect, $request->validated());

        return $this->respond($prospect, 'Prospect assigned successfully');
    }

    public function reconcile(Prospect $prospect): JsonResponse
    {
        $this->authorize('update', $prospect);

        $prospect = $this->prospects->reconcile($prospect);

        return $this->respond($prospect, 'Prospect reconciled successfully');
    }

    public function destroy(Prospect $prospect): JsonResponse
    {
        $this->authorize('delete', $prospect);

        $this->prospects->delete($prospect);

        return response()->json([
            'message' => 'Prospect deleted successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function import(ImportProspectsRequest $request): JsonResponse
    {
        $this->authorize('import', Prospect::class);

        $data = $request->validated();

        if ($request->boolean('sync')) {
            $result = $this->prospects->importFromApify($data);

            return response()->json([
                'message' => 'Prospects imported successfully',
                'data' => $result,
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ]);
        }

        $import = $this->prospects->startImport($data, $request->user());

        return (new ProspectImportResource($import))
            ->additional([
                'message' => 'Prospect import queued successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(202);
    }

    public function importStatus(ProspectImport $import): JsonResponse
    {
        $this->authorize('import', Prospect::class);

        return (new ProspectImportResource($import))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    private function respond(Prospect $prospect, string $message): JsonResponse
    {
        return (new ProspectResource($prospect))
            ->additional([
                'message' => $message,
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
