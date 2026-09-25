<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserAddressRequest;
use App\Http\Requests\User\UpdateUserAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Services\User\AddressService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Carnet d'adresses du client : les lieux où un pro peut venir.
 *
 * @tags Addresses
 */
class UserAddressController extends Controller
{
    public function __construct(
        private readonly AddressService $addresses,
    ) {}

    /**
     * List my addresses
     *
     * L'adresse par défaut en premier.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return UserAddressResource::collection($this->addresses->forUser($request->user()));
    }

    /**
     * Add an address
     *
     * La première adresse devient l'adresse par défaut.
     */
    public function store(StoreUserAddressRequest $request): UserAddressResource
    {
        return new UserAddressResource($this->addresses->create($request->user(), $request->validated()));
    }

    /**
     * Update an address
     *
     * is_default à true en fait l'adresse par défaut ; pour changer d'adresse par défaut, on en désigne une autre.
     */
    public function update(UpdateUserAddressRequest $request, UserAddress $address): UserAddressResource
    {
        $this->authorize('update', $address);

        return new UserAddressResource($this->addresses->update($address, $request->validated()));
    }

    /**
     * Delete an address
     *
     * Si c'était l'adresse par défaut, la plus récente des autres la remplace.
     */
    public function destroy(UserAddress $address): Response
    {
        $this->authorize('delete', $address);

        $this->addresses->delete($address);

        return response()->noContent();
    }
}
