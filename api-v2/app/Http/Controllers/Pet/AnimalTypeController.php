<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalTypeResource;
use App\Models\AnimalType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Pets
 */
class AnimalTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AnimalTypeResource::collection(
            AnimalType::with(['attributeDefinitions.options'])->orderBy('code')->get()
        );
    }
}
