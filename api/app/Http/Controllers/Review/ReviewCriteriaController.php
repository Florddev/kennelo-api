<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewCriteriaDefinitionResource;
use App\Models\ReviewCriteriaDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewCriteriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applicableTo = $request->query('applicable_to');
        $filter = is_string($applicableTo) && in_array($applicableTo, ['user', 'activity', 'both'], true)
            ? $applicableTo
            : 'all';

        $criteria = Cache::rememberForever(
            "reference:review_criteria:{$filter}",
            fn () => ReviewCriteriaDefinition::query()
                ->when($filter !== 'all', fn ($q) => $q->where('applicable_to', $filter))
                ->orderBy('applicable_to')
                ->orderBy('sort_order')
                ->get()
        );

        return ReviewCriteriaDefinitionResource::collection($criteria)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
