<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Establishment;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class EstablishmentReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function index(ListReviewsRequest $request, Establishment $establishment): JsonResponse
    {
        $reviews = $this->reviewService->forEstablishment($establishment, $request->validated());
        $aggregates = $this->reviewService->aggregatesForEstablishment($establishment);

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
                'aggregates' => $aggregates,
            ])
            ->response();
    }
}
