<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Profession;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profession\StoreProfessionRequest;
use App\Http\Requests\Admin\Profession\UpdateProfessionRequest;
use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use App\Services\Profession\ProfessionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Un métier ne se supprime pas : on le ferme (is_active) pour qu'aucune activité ne s'y crée plus.
 *
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
     * Tous les métiers, ouverts ou fermés, avec leurs espèces, leurs justificatifs et leur nombre d'activités.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Profession::class);

        return ProfessionResource::collection($this->professions->professions());
    }

    public function store(StoreProfessionRequest $request): ProfessionResource
    {
        $this->authorize('create', Profession::class);

        return new ProfessionResource($this->professions->createProfession($request->validated()));
    }

    /**
     * Update a profession
     *
     * Ce que des activités utilisent déjà ne peut pas leur être retiré : mode de réservation, lieu, espèce.
     * La liste des justificatifs, si elle est envoyée, remplace l'existante.
     */
    public function update(UpdateProfessionRequest $request, Profession $profession): ProfessionResource
    {
        $this->authorize('update', $profession);

        return new ProfessionResource($this->professions->updateProfession($profession, $request->validated()));
    }
}
