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
     * La période de base, obligatoire, ne compte pas : le quota porte sur les périodes saisonnières.
     */
    public function assertCanAddPeriod(Organization $organization): void
    {
        $this->assertBelowLimit($organization, 'max_periods', $organization->pricingPeriods()->seasonal()->count(), 'periods');
    }

    /**
     * Quand les activités en trop sont mises en pause au retour à une offre inférieure (réglage
     * soft_disable_activities), le quota compte aussi les activités ouvertes : une activité en pause ne rouvre
     * que dans la limite de l'offre. Sans ce réglage, l'entreprise garde ce qu'elle avait.
     */
    public function assertCanReopenActivity(Activity $activity): void
    {
        if (! setting('soft_disable_activities')) {
            return;
        }

        $organization = $activity->organization()->firstOrFail();

        $this->assertBelowLimit(
            $organization,
            'max_activities',
            $organization->activities()->where('is_active', true)->whereKeyNot($activity->id)->count(),
            'activities',
        );
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
