<?php

declare(strict_types=1);

namespace App\Services\Admin\Activity;

use App\Enums\ActivityStatusEnum;
use App\Enums\PaginationEnum;
use App\Models\Activity;
use App\Models\User;
use App\Services\Prospect\CompanyLookupService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ActivityAdminService
{
    public function __construct(
        private CompanyLookupService $companyLookup,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Activity::query()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(
                isset($filters['professional']),
                fn ($q) => $filters['professional']
                    ? $q->whereNotNull('siret')
                    : $q->whereNull('siret'),
            )
            ->when(
                $filters['department'] ?? null,
                fn ($q, $dept) => $q->whereHas('address', fn ($sub) => $sub->where('department', $dept)),
            )
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->where('name', 'like', "%{$search}%"),
            )
            ->when(
                $filters['sort_by'] ?? null,
                fn ($q, $sort) => $q->orderBy($sort, $filters['sort_direction'] ?? 'asc'),
                fn ($q) => $q->latest(),
            )
            ->with(['address', 'manager', 'reviewedBy'])
            ->paginate($perPage);
    }

    public function find(string $id): Activity
    {
        return Activity::with(['address', 'manager', 'reviewedBy'])->findOrFail($id);
    }

    public function approve(Activity $activity, User $admin): Activity
    {
        $activity->update([
            'status' => ActivityStatusEnum::APPROVED->value,
            'is_active' => true,
            'rejection_reason' => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => Carbon::now(),
        ]);

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }

    public function reject(Activity $activity, User $admin, array $data): Activity
    {
        $activity->update([
            'status' => ActivityStatusEnum::REJECTED->value,
            'is_active' => false,
            'rejection_reason' => $data['reason'],
            'reviewed_by' => $admin->id,
            'reviewed_at' => Carbon::now(),
        ]);

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }

    public function update(Activity $activity, array $data): Activity
    {
        $activity->update($data);

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }

    public function verifyCompany(Activity $activity): Activity
    {
        $company = null;

        if ($activity->siret !== null) {
            $company = $this->companyLookup->findBySiret($activity->siret);
        }

        if ($company === null && $activity->siren !== null) {
            $company = $this->companyLookup->findBySiren($activity->siren);
        }

        if ($company === null) {
            $company = $this->companyLookup->searchByText($activity->name);
        }

        if ($company !== null) {
            $activity->update([
                'siret' => $company['siret'] ?? $activity->siret,
                'siren' => $company['siren'] ?? $activity->siren,
                'ape_code' => $company['ape_code'] ?? $activity->ape_code,
                'company_verified_at' => Carbon::now(),
                'company_verification_data' => $company,
            ]);
        }

        return $activity->fresh(['address', 'manager', 'reviewedBy']);
    }
}
