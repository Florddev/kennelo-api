<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminActionsRequest;
use App\Http\Resources\AdminActionResource;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Audit
 */
class AuditController extends Controller
{
    public function __construct(
        private AdminActionService $actions
    ) {}

    public function index(ListAdminActionsRequest $request): AnonymousResourceCollection
    {
        return AdminActionResource::collection($this->actions->paginate($request->validated()));
    }
}
