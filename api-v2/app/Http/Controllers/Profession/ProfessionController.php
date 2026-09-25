<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profession;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfessionCategoryResource;
use App\Services\Profession\ProfessionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Professions
 */
class ProfessionController extends Controller
{
    public function __construct(
        private readonly ProfessionService $professions,
    ) {}

    /**
     * List professions
     *
     * Métiers ouverts, regroupés par catégorie, avec leurs espèces. Public.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProfessionCategoryResource::collection($this->professions->catalog());
    }
}
