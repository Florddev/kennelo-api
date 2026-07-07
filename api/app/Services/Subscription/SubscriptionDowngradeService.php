<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\NotificationTypeEnum;
use App\Enums\PlanEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;

class SubscriptionDowngradeService
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}

    public function applyForUser(User $user): void
    {
        $plan = $user->effectivePlan();

        if ($plan !== PlanEnum::FREE) {
            return;
        }

        DB::transaction(function () use ($user, $plan): void {
            $activities = Activity::where('manager_id', $user->id)->get();

            foreach ($activities as $activity) {
                $this->softDisableSurplusCycles($activity, $plan);
            }
        });

        $this->notifications->notify(
            $user,
            NotificationTypeEnum::SUBSCRIPTION_DOWNGRADED,
            ['user_id' => $user->id],
        );
    }

    private function softDisableSurplusCycles(Activity $activity, PlanEnum $plan): void
    {
        if (! setting('soft_disable_cycles', config('plans.downgrade.soft_disable.cycles', false))) {
            return;
        }

        $limit = $plan->limit('max_cycles_per_activity');

        if ($limit === null || $limit < 0) {
            return;
        }

        $activeCycleIds = ActivityCycle::where('activity_id', $activity->id)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->pluck('id');

        $surplus = $activeCycleIds->slice($limit)->values();

        if ($surplus->isEmpty()) {
            return;
        }

        ActivityCycle::whereIn('id', $surplus->all())->update(['is_active' => false]);
    }
}
