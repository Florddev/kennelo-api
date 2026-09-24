<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\AdminActionTypeEnum;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminActionService
{
    public function log(User $admin, ?User $target, AdminActionTypeEnum $action, array $metadata = []): AdminAction
    {
        return AdminAction::create([
            'admin_id' => $admin->id,
            'target_user_id' => $target?->id,
            'action' => $action,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? null;

        return AdminAction::with(['admin', 'target'])
            ->when(isset($filters['action']), fn ($q) => $q->where('action', $filters['action']))
            ->when(isset($filters['admin_id']), fn ($q) => $q->where('admin_id', $filters['admin_id']))
            ->when(isset($filters['user_id']), fn ($q) => $q->where('target_user_id', $filters['user_id']))
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
