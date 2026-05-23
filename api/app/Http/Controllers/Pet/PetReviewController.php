<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Pet;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class PetReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function index(ListReviewsRequest $request, Pet $pet): JsonResponse
    {
        $reviews = $this->reviewService->forPet($pet, $request->validated());
        $aggregates = $this->reviewService->aggregatesForPet($pet);

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
                'aggregates' => $aggregates,
            ])
            ->response();
    }
}
