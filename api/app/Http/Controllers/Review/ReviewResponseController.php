<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewResponseRequest;
use App\Http\Resources\ReviewResponseResource;
use App\Models\Review;
use App\Services\Review\ReviewResponseService;
use Illuminate\Http\JsonResponse;

class ReviewResponseController extends Controller
{
    public function __construct(
        private ReviewResponseService $responseService,
    ) {}

    public function store(StoreReviewResponseRequest $request, Review $review): JsonResponse
    {
        $this->authorize('respond', $review);

        $response = $this->responseService->create($request->user(), $review, $request->validated());

        return (new ReviewResponseResource($response))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }
}
