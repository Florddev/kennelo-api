<?php

declare(strict_types=1);

namespace App\Services\Admin\Organization;

use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationStatusEnum;
use App\Models\Organization;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Organization\CompanyLookupService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Validation des entreprises par l'équipe Kennelo.
 */
class OrganizationReviewService
{
    public function __construct(
        private readonly CompanyLookupService $companies,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Organization::query()
            ->with(['owner.media', 'address', 'subscription.plan'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['search']), function ($query) use ($filters): void {
                $query->where(fn ($query) => $query
                    ->where('legal_name', 'like', "%{$filters['search']}%")
                    ->orWhere('siren', $filters['search']));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    public function approve(Organization $organization, User $admin): Organization
    {
        $this->review($organization, $admin, OrganizationStatusEnum::VERIFIED, null);
        $this->notifyOwner($organization, NotificationTypeEnum::ORGANIZATION_APPROVED);

        return $organization;
    }

    public function reject(Organization $organization, User $admin, string $reason): Organization
    {
        $this->review($organization, $admin, OrganizationStatusEnum::REJECTED, $reason);
        $this->notifyOwner($organization, NotificationTypeEnum::ORGANIZATION_REJECTED, ['reason' => $reason]);

        return $organization;
    }

    public function suspend(Organization $organization, User $admin, string $reason): Organization
    {
        $this->review($organization, $admin, OrganizationStatusEnum::SUSPENDED, $reason);
        $this->notifyOwner($organization, NotificationTypeEnum::ORGANIZATION_SUSPENDED, ['reason' => $reason]);

        return $organization;
    }

    /**
     * Compare l'entreprise au registre public. Le résultat est conservé pour la décision de l'admin,
     * il ne valide rien à lui seul.
     */
    public function verifyCompany(Organization $organization): Organization
    {
        if (blank($organization->siren)) {
            throw ValidationException::withMessages(['siren' => __('organization.no_siren')]);
        }

        $company = $this->companies->findBySiren((string) $organization->siren);

        if ($company === null) {
            throw ValidationException::withMessages(['siren' => __('organization.company_not_found')]);
        }

        $organization->forceFill(['verification_data' => $company])->save();

        return $organization;
    }

    private function review(Organization $organization, User $admin, OrganizationStatusEnum $status, ?string $reason): void
    {
        $organization->forceFill([
            'status' => $status,
            'verified_at' => $status === OrganizationStatusEnum::VERIFIED ? now() : $organization->verified_at,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function notifyOwner(Organization $organization, NotificationTypeEnum $type, array $data = []): void
    {
        if ($organization->owner !== null) {
            $this->notifications->notify($organization->owner, $type, array_merge([
                'organization_id' => $organization->id,
                'organization_name' => $organization->legal_name,
            ], $data));
        }
    }
}
