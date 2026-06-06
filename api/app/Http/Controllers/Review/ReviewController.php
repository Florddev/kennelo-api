<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function show(Review $review): JsonResponse
    {
        $this->authorize('view', $review);

        $review = $this->reviewService->show($review);

        return (new ReviewResource($review))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
