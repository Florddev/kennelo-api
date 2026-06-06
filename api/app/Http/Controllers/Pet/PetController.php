<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Services\Pet\PetService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Pets
 */
class PetController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function index(): JsonResponse
    {
        $pets = $this->petService->getUserPets(auth()->user());

        return PetResource::collection($pets)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $pet->load(['animalType', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media']);

        return (new PetResource($pet))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }

    public function store(StorePetRequest $request): JsonResponse
    {
        $pet = $this->petService->create($request->user(), $request->validated());

        return (new PetResource($pet))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePetRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $pet = $this->petService->update($pet, $request->validated());

        return (new PetResource($pet))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Pet $pet): JsonResponse
    {
        $this->authorize('delete', $pet);

        $this->petService->delete($pet);

        return response()->json(null, 204);
    }
}
