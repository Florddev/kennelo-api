<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\OrganizationMemberStatusEnum;
use App\Models\Activity;
use App\Models\Organization;
use App\Services\MediaService;
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

    public function assertCanAddActivity(Organization $organization): void
    {
        $this->assertBelowLimit($organization, 'max_activities', $organization->activities()->count(), 'activities');
    }

    /**
     * Le quota de photos s'entend par activité.
     */
    public function assertCanAddPhotos(Activity $activity, int $incoming): void
    {
        $this->assertBelowLimit(
            $activity->organization()->firstOrFail(),
            'max_photos',
            $activity->media()->where('collection_name', MediaService::COLLECTION_IMAGES)->count(),
            'photos',
            $incoming,
        );
    }

    private function assertBelowLimit(Organization $organization, string $limitKey, int $current, string $messageKey, int $incoming = 1): void
    {
        $plan = $organization->effectivePlan();

        if ($plan->isUnlimited($limitKey)) {
            return;
        }

        $limit = (int) $plan->limit($limitKey);

        if ($current + $incoming > $limit) {
            throw ValidationException::withMessages([
                'plan' => __('plans.limit_reached.'.$messageKey, ['limit' => $limit]),
            ]);
        }
    }
}
