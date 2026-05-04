<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Resources\ReviewResource;
use App\Models\User;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class UserReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function index(ListReviewsRequest $request, User $user): JsonResponse
    {
        $reviews = $this->reviewService->forUser($user, $request->validated());
        $aggregates = $this->reviewService->aggregatesForUser($user);

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
                'aggregates' => $aggregates,
            ])
            ->response();
    }
}
