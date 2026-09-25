<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Organization;

/**
 * Ramène une entreprise dans les quotas de son offre quand son abonnement cesse (config plans.downgrade).
 *
 * Désactivé par défaut : rien n'est supprimé, et l'entreprise ne peut simplement plus rien ajouter au-delà
 * du quota. Activé, les activités en trop sont mises en pause, les plus anciennes restant ouvertes ;
 * le propriétaire les rouvre à son gré dans la limite de son offre. Les périodes suivront avec les tarifs.
 */
class SubscriptionDowngradeService
{
    /**
     * @return int nombre d'activités mises en pause
     */
    public function apply(Organization $organization): int
    {
        if (! config('plans.downgrade.soft_disable.activities')) {
            return 0;
        }

        $plan = $organization->effectivePlan();

        if ($plan->isUnlimited('max_activities')) {
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
