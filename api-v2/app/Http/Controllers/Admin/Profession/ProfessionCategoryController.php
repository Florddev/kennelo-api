<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Profession;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profession\StoreProfessionCategoryRequest;
use App\Http\Requests\Admin\Profession\UpdateProfessionCategoryRequest;
use App\Http\Resources\ProfessionCategoryResource;
use App\Models\ProfessionCategory;
use App\Services\Profession\ProfessionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Admin Professions
 */
class ProfessionCategoryController extends Controller
{
    public function __construct(
        private readonly ProfessionService $professions,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProfessionCategory::class);

        return ProfessionCategoryResource::collection($this->professions->categories());
    }

    public function store(StoreProfessionCategoryRequest $request): ProfessionCategoryResource
    {
        $this->authorize('create', ProfessionCategory::class);

        return new ProfessionCategoryResource($this->professions->createCategory($request->validated()));
    }

    public function update(UpdateProfessionCategoryRequest $request, ProfessionCategory $category): ProfessionCategoryResource
    {
        $this->authorize('update', $category);

        return new ProfessionCategoryResource($this->professions->updateCategory($category, $request->validated()));
    }

    /**
     * Delete a category
     *
     * Refusé tant que la catégorie contient des métiers.
     */
    public function destroy(ProfessionCategory $category): Response
    {
        $this->authorize('delete', $category);

        $this->professions->deleteCategory($category);

        return response()->noContent();
    }
}
