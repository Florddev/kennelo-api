<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminActionsRequest;
use App\Http\Resources\AdminActionResource;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Admin Audit
 */
class AdminActionController extends Controller
{
    public function __construct(
        private AdminActionService $actions
    ) {}

    public function index(ListAdminActionsRequest $request): JsonResponse
    {
        $actions = $this->actions->paginate($request->validated());

        return AdminActionResource::collection($actions)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }
}
