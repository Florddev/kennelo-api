<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Organization;

/**
 * Ramène une entreprise dans les quotas de son offre quand son abonnement cesse. Rien n'est jamais supprimé.
 *
 * Chaque quota a son réglage dans le back-office (groupe « downgrade ») :
 * - soft_disable_activities : les activités en trop sont mises en pause, les plus anciennes restant ouvertes.
 *   Le propriétaire choisit ensuite lesquelles rouvrir, dans la limite de son offre (PlanLimitService).
 * - soft_disable_photos : les photos au-delà du quota sont masquées au public (Activity::publicImages()).
 * - soft_disable_periods : les périodes tarifaires au-delà du quota ne s'appliquent plus aux prix
 *   (ActivityPricingService::calculator()).
 * Ces deux-là se lisent à l'affichage et au calcul : rien à écrire ici, et tout revient si l'offre remonte.
 *
 * Réglage désactivé, l'entreprise garde ce qu'elle a et ne peut simplement plus rien ajouter au-delà du quota.
 */
class SubscriptionDowngradeService
{
    /**
     * @return int nombre d'activités mises en pause
     */
    public function apply(Organization $organization): int
    {
        return $this->pauseSurplusActivities($organization);
    }

    private function pauseSurplusActivities(Organization $organization): int
    {
        $plan = $organization->effectivePlan();

        if (! setting('soft_disable_activities') || $plan->isUnlimited('max_activities')) {
            return 0;
        }

        $kept = $organization->activities()
            ->where('is_active', true)
            ->oldest()
            ->orderBy('id')
            ->limit((int) $plan->limit('max_activities'))
            ->pluck('id');

        return $organization->activities()
            ->where('is_active', true)
            ->whereKeyNot($kept->all())
            ->update(['is_active' => false]);
    }
}
