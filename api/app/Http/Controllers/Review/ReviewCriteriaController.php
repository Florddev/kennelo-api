<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewCriteriaDefinitionResource;
use App\Models\ReviewCriteriaDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewCriteriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applicableTo = $request->query('applicable_to');

        $criteria = ReviewCriteriaDefinition::query()
            ->when(is_string($applicableTo) && in_array($applicableTo, ['user', 'establishment', 'both'], true),
                fn ($q) => $q->where('applicable_to', $applicableTo)
            )
            ->orderBy('applicable_to')
            ->orderBy('sort_order')
            ->get();

        return ReviewCriteriaDefinitionResource::collection($criteria)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
