<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\OrganizationMemberStatusEnum;
use App\Models\Organization;
use Illuminate\Validation\ValidationException;

/**
 * Vérifie les quotas de l'offre de l'entreprise avant d'ajouter un élément compté.
 */
class PlanLimitService
{
    /**
     * Les invitations en attente comptent : elles deviendront des membres sans nouvelle vérification.
     */
    public function assertCanAddMember(Organization $organization): void
    {
        $this->assertBelowLimit(
            $organization,
            'max_members',
            $organization->members()->whereIn('status', [OrganizationMemberStatusEnum::ACTIVE, OrganizationMemberStatusEnum::PENDING])->count(),
            'members',
        );
    }

    private function assertBelowLimit(Organization $organization, string $limitKey, int $current, string $messageKey): void
    {
        $plan = $organization->effectivePlan();

        if ($plan->isUnlimited($limitKey)) {
            return;
        }

        $limit = (int) $plan->limit($limitKey);

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'plan' => __('plans.limit_reached.'.$messageKey, ['limit' => $limit]),
            ]);
        }
    }
}
