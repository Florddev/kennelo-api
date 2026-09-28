<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Organization;
use App\Services\Activity\ActivityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Activities
 */
class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityService $activities,
    ) {}

    /**
     * List the activities of an organization
     *
     * Toutes les activités de l'entreprise, quel que soit leur statut. Réservé à l'équipe.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return ActivityResource::collection($this->activities->forOrganization($organization));
    }

    /**
     * Create an activity
     *
     * L'activité est créée en attente de validation par Kennelo. Compte dans le quota d'activités de l'offre.
     */
    public function store(StoreActivityRequest $request, Organization $organization): ActivityResource
    {
        $this->authorize('create', [Activity::class, $organization]);

        return new ActivityResource($this->activities->create($organization, $request->validated()));
    }

    /**
     * Show an activity
     *
     * Publique une fois l'activité réservable ; avant, visible de son équipe et des admins seulement.
     */
    public function show(Request $request, Activity $activity): ActivityResource
    {
        $this->authorize('view', $activity);

        $activity->load(ActivityService::RELATIONS)->loadExists(Activity::missingDocumentsCheck())->loadRating();

        if ($request->user() !== null) {
            $activity->loadExists(['favoritedBy as is_favorited' => fn (Builder $query) => $query->whereKey($request->user()->id)]);
        }

        return new ActivityResource($activity);
    }

    /**
     * Update an activity
     *
     * Le métier ne change pas. Les lieux et les espèces restent pris parmi ceux du métier.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): ActivityResource
    {
        $this->authorize('update', $activity);

        return new ActivityResource($this->activities->update($activity, $request->validated()));
    }

    /**
     * Delete an activity
     *
     * Refusé tant qu'une réservation est en cours ou à venir. L'historique reste lisible.
     */
    public function destroy(Activity $activity): Response
    {
        $this->authorize('delete', $activity);

        $this->activities->delete($activity);

        return response()->noContent();
    }
}
