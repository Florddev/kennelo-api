<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Requests\Review\StoreReviewReportRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\StoreReviewResponseRequest;
use App\Http\Resources\ReviewReportResource;
use App\Http\Resources\ReviewResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Services\Review\ReviewReportService;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Avis croisés sur les réservations terminées : le client note l'activité, l'équipe note le client. Un avis
 * reste caché jusqu'à ce que l'autre partie ait donné le sien, ou jusqu'à la fin du délai.
 *
 * @tags Reviews
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviews,
        private readonly ReviewReportService $reports,
    ) {}

    /**
     * Review a booking
     *
     * Pour le client, qui note l'activité, et pour l'équipe (bookings.manage), qui note le client. La réservation
     * doit être terminée depuis moins de reviews.window_days jours (14 par défaut). L'autre partie est prévenue
     * sans lire l'avis.
     */
    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('create', [Review::class, $booking]);

        return ReviewResource::make($this->reviews->create($request->user(), $booking, $request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List the reviews of an activity
     *
     * Avis publiés des clients, les plus récents d'abord, avec la note moyenne, le nombre d'avis et leur
     * répartition par étoile (rating). L'équipe (bookings.view) y lit aussi les retours privés.
     */
    public function forActivity(ListReviewsRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('view', $activity);

        return ReviewResource::collection($this->reviews->forActivity($activity, $request->validated()))
            ->additional(['rating' => $this->reviews->rating($this->reviews->publishedForActivity($activity))]);
    }

    /**
     * List the reviews about a client
     *
     * Avis publiés des équipes sur ce client, avec sa note moyenne.
     */
    public function aboutClient(ListReviewsRequest $request, User $user): AnonymousResourceCollection
    {
        return ReviewResource::collection($this->reviews->aboutClient($user, $request->validated()))
            ->additional(['rating' => $this->reviews->rating($this->reviews->aboutClientQuery($user)->published())]);
    }

    /**
     * List the reviews I gave
     *
     * En tant que client ou pour une équipe, publiés ou non.
     */
    public function given(ListReviewsRequest $request): AnonymousResourceCollection
    {
        return ReviewResource::collection($this->reviews->given($request->user(), $request->validated()));
    }

    /**
     * List the reviews I received
     *
     * Avis publiés des équipes sur moi en tant que client, avec leurs retours privés. Ceux des clients sur une
     * activité se lisent par /activities/{activity}/reviews.
     */
    public function received(ListReviewsRequest $request): AnonymousResourceCollection
    {
        return ReviewResource::collection($this->reviews->aboutClient($request->user(), $request->validated()))
            ->additional(['rating' => $this->reviews->rating($this->reviews->aboutClientQuery($request->user())->published())]);
    }

    /**
     * Show a review
     */
    public function show(Review $review): ReviewResource
    {
        $this->authorize('view', $review);

        return new ReviewResource($review->load(ReviewService::RELATIONS));
    }

    /**
     * Respond to a review
     *
     * Réponse publique de la partie notée, une seule par avis publié : l'équipe (bookings.manage) pour un avis de
     * client, le client pour un avis de l'équipe.
     */
    public function respond(StoreReviewResponseRequest $request, Review $review): JsonResponse
    {
        $this->authorize('respond', $review);
        $this->reviews->respond($request->user(), $review, $request->validated('response'));

        return ReviewResource::make($review->load(ReviewService::RELATIONS))->response()->setStatusCode(201);
    }

    /**
     * Report a review
     *
     * Un avis publié, une fois par personne, pas le sien. Les admins sont prévenus.
     */
    public function report(StoreReviewReportRequest $request, Review $review): JsonResponse
    {
        $this->authorize('report', $review);

        return ReviewReportResource::make($this->reports->report($request->user(), $review, $request->validated()))
            ->response()
            ->setStatusCode(201);
    }
}
