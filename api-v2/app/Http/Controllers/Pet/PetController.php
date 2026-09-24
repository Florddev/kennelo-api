<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\StorePetRequest;
use App\Http\Requests\Pet\UpdatePetRequest;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Services\Pet\PetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Pets
 */
class PetController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PetResource::collection($this->petService->getUserPets($request->user()));
    }

    public function show(Pet $pet): PetResource
    {
        $this->authorize('view', $pet);

        $pet->load(['animalType', 'animalBreed', 'petAttributes.attributeDefinition', 'petAttributes.attributeOption', 'media']);

        return new PetResource($pet);
    }

    public function store(StorePetRequest $request): PetResource
    {
        return new PetResource($this->petService->create($request->user(), $request->validated()));
    }

    public function update(UpdatePetRequest $request, Pet $pet): PetResource
    {
        $this->authorize('update', $pet);

        return new PetResource($this->petService->update($pet, $request->validated()));
    }

    public function destroy(Pet $pet): Response
    {
        $this->authorize('delete', $pet);

        $this->petService->delete($pet);

        return response()->noContent();
    }
}
