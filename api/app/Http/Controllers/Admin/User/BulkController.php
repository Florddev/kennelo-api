<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\User;

use App\Enums\AdminActionTypeEnum;
use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\BulkRolesRequest;
use App\Http\Requests\Admin\User\BulkStatusRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\User\AdminUserService;
use App\Services\Admin\User\ExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Admin Users Bulk
 */
class BulkController extends Controller
{
    public function __construct(
        private AdminUserService $adminUserService,
        private ExportService $export,
        private AdminActionService $actions
    ) {}

    public function status(BulkStatusRequest $request): JsonResponse
    {
        $this->authorize('bulkManage', User::class);

        $data = $request->validated();

        $affected = $this->adminUserService->bulkStatus(
            $data['user_ids'],
            (int) $data['status'],
            $request->user()->id
        );

        $this->actions->log($request->user(), null, AdminActionTypeEnum::BULK_STATUS, [
            'status' => $data['status'],
            'affected' => $affected,
        ]);

        return response()->json([
            'message' => 'Users status updated successfully',
            'affected' => $affected,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function roles(BulkRolesRequest $request): JsonResponse
    {
        $this->authorize('bulkManage', User::class);

        $data = $request->validated();

        $affected = $this->adminUserService->bulkRoles(
            $data['user_ids'],
            $data['action'],
            $data['roles']
        );

        $this->actions->log($request->user(), null, AdminActionTypeEnum::BULK_ROLES, [
            'action' => $data['action'],
            'roles' => $data['roles'],
            'affected' => $affected,
        ]);

        return response()->json([
            'message' => 'Users roles updated successfully',
            'affected' => $affected,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function export(ListUsersRequest $request): StreamedResponse
    {
        $this->authorize('bulkManage', User::class);

        $this->actions->log($request->user(), null, AdminActionTypeEnum::EXPORT, $request->validated());

        return $this->export->streamCsv($request->validated());
    }
}
