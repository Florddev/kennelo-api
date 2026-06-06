<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class BookingReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('create', [Review::class, $booking]);

        $review = $this->reviewService->create($request->user(), $booking, $request->validated());

        return (new ReviewResource($review))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }
}
