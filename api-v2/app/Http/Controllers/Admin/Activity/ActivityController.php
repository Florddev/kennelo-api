<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Enums\AdminActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activity\ListActivitiesRequest;
use App\Http\Requests\Admin\Activity\ReviewActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Activity\ActivityService;
use App\Services\Admin\Activity\ActivityReviewService;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Activities
 */
class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityReviewService $reviews,
        private readonly AdminActionService $actions,
    ) {}

    public function index(ListActivitiesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Activity::class);

        return ActivityResource::collection($this->reviews->paginate($request->validated()));
    }

    public function show(Activity $activity): ActivityResource
    {
        $this->authorize('review', $activity);

        return new ActivityResource($activity->load(ActivityService::RELATIONS)->loadExists(Activity::missingDocumentsCheck()));
    }

    /**
     * Approve an activity
     *
     * Refusé tant qu'un justificatif obligatoire du métier manque ou n'est pas valable. L'activité devient
     * réservable dès que son entreprise est vérifiée et peut encaisser.
     */
    public function approve(Request $request, Activity $activity): ActivityResource
    {
        $this->authorize('review', $activity);

        $this->reviews->approve($activity, $request->user());
        $this->log($request, $activity, AdminActionTypeEnum::APPROVE_ACTIVITY);

        return new ActivityResource($activity->load(ActivityService::RELATIONS));
    }

    public function reject(ReviewActivityRequest $request, Activity $activity): ActivityResource
    {
        $this->authorize('review', $activity);

        $this->reviews->reject($activity, $request->user(), $request->validated('reason'));
        $this->log($request, $activity, AdminActionTypeEnum::REJECT_ACTIVITY, $request->validated());

        return new ActivityResource($activity->load(ActivityService::RELATIONS));
    }

    public function suspend(ReviewActivityRequest $request, Activity $activity): ActivityResource
    {
        $this->authorize('review', $activity);

        $this->reviews->suspend($activity, $request->user(), $request->validated('reason'));
        $this->log($request, $activity, AdminActionTypeEnum::SUSPEND_ACTIVITY, $request->validated());

        return new ActivityResource($activity->load(ActivityService::RELATIONS));
    }

    /**
     * Le journal admin vise des utilisateurs : l'action est rattachée au propriétaire de l'entreprise,
     * l'activité est en métadonnée.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function log(Request $request, Activity $activity, AdminActionTypeEnum $action, array $metadata = []): void
    {
        $this->actions->log($request->user(), $activity->organization?->owner, $action, ['activity_id' => $activity->id, ...$metadata]);
    }
}
