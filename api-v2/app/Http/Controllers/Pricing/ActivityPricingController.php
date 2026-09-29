<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\PriceCalendarRequest;
use App\Http\Requests\Pricing\ReplacePeriodPricesRequest;
use App\Http\Requests\Pricing\UpdatePeriodSettingRequest;
use App\Http\Resources\ActivityPeriodSettingResource;
use App\Http\Resources\PricingPeriodResource;
use App\Models\Activity;
use App\Models\PricingPeriod;
use App\Services\Pricing\ActivityPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tarifs des séjours d'une activité.
 *
 * @tags Pricing
 */
class ActivityPricingController extends Controller
{
    public function __construct(
        private readonly ActivityPricingService $pricing,
    ) {}

    /**
     * List the pricing of an activity
     *
     * Toutes les périodes de l'entreprise, chacune avec le réglage et la grille de l'activité (setting null tant
     * qu'elle ne l'applique pas).
     */
    public function index(Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return PricingPeriodResource::collection($this->pricing->periods($activity));
    }

    /**
     * Configure a period for an activity
     */
    public function updateSetting(UpdatePeriodSettingRequest $request, Activity $activity, PricingPeriod $pricingPeriod): ActivityPeriodSettingResource
    {
        $this->authorize('price', [$activity, $pricingPeriod]);

        return new ActivityPeriodSettingResource($this->pricing->updateSetting($activity, $pricingPeriod, $request->validated()));
    }

    /**
     * Replace the price grid of a period
     *
     * Un prix par place, pour tous les jours ou pour un jour de la semaine. Le prix du jour l'emporte ; une place
     * sans prix dans la période prend celui de la période de base, majoré du pourcentage du réglage.
     */
    public function updatePrices(ReplacePeriodPricesRequest $request, Activity $activity, PricingPeriod $pricingPeriod): ActivityPeriodSettingResource
    {
        $this->authorize('price', [$activity, $pricingPeriod]);

        return new ActivityPeriodSettingResource($this->pricing->replacePrices($activity, $pricingPeriod, $request->validated('prices')));
    }

    /**
     * Price calendar
     *
     * Pour chaque date : ouverture, séjour minimum d'un séjour qui y commence, et pour chaque place active son prix
     * pour un animal et le nombre de places libres. 93 jours au plus.
     *
     * @unauthenticated
     */
    public function calendar(PriceCalendarRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        return response()->json($this->pricing->calendar($activity, $request->date('from'), $request->date('to')));
    }
}
