<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\UpsertPetAttributesRequest;
use App\Http\Resources\PetAttributeResource;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Services\Pet\PetService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Pets
 */
class PetAttributeController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function upsert(UpsertPetAttributesRequest $request, Pet $pet): AnonymousResourceCollection
    {
        $this->authorize('update', $pet);

        /** @var AnimalType $animalType */
        $animalType = $pet->animalType()->firstOrFail();

        $this->petService->validateAttributesForAnimalType($request->validated('attributes'), $animalType);
        $this->petService->syncAttributes($pet, $request->validated('attributes'));

        $pet->load(['petAttributes.attributeDefinition', 'petAttributes.attributeOption']);

        return PetAttributeResource::collection($pet->petAttributes);
    }
}
