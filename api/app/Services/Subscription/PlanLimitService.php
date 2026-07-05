<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\PlanEnum;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    public function userPlan(User $user): PlanEnum
    {
        $activities = Activity::where('manager_id', $user->id)
            ->with('subscription.plan')
            ->get();

        $best = PlanEnum::FREE;
        $bestRank = $this->rank($best);

        foreach ($activities as $activity) {
            $rank = $this->rank($activity->effectivePlan());

            if ($rank > $bestRank) {
                $best = $activity->effectivePlan();
                $bestRank = $rank;
            }
        }

        return $best;
    }

    public function assertCanCreateActivity(User $user): void
    {
        $limit = $this->userPlan($user)->limit('max_activities');

        if ($limit === null || $limit < 0) {
            return;
        }

        $current = $user->managedActivities()->count();

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'plan' => [__('plans.limit_reached.activities', ['limit' => (string) $limit])],
            ]);
        }
    }

    public function assertCanCreateCycle(Activity $activity): void
    {
        $limit = $activity->effectivePlan()->limit('max_cycles_per_activity');

        if ($limit === null || $limit < 0) {
            return;
        }

        $current = $activity->cycles()->count();

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'plan' => [__('plans.limit_reached.cycles', ['limit' => (string) $limit])],
            ]);
        }
    }

    public function maxPhotos(Activity $activity): ?int
    {
        return $activity->effectivePlan()->limit('max_photos');
    }

    private function rank(PlanEnum $plan): int
    {
        return match ($plan) {
            PlanEnum::FREE => 0,
            PlanEnum::STARTER => 1,
            PlanEnum::PRO => 2,
        };
    }
}
