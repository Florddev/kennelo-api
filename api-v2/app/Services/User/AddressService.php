<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Address;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carnet d'adresses du client. Il a toujours une adresse par défaut dès qu'il en a une :
 * la première le devient d'office, et la suppression de celle par défaut en désigne une autre.
 */
class AddressService
{
    /**
     * @return Collection<int, UserAddress>
     */
    public function forUser(User $user): Collection
    {
        return $user->addresses()
            ->with('address')
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): UserAddress
    {
        return DB::transaction(function () use ($user, $data): UserAddress {
            $isDefault = ($data['is_default'] ?? false) || ! $user->addresses()->exists();

            if ($isDefault) {
                $this->clearDefault($user);
            }

            return $user->addresses()->create([
                'address_id' => Address::create($data['address'])->id,
                'label' => $data['label'],
                'is_default' => $isDefault,
            ])->load('address');
        });
    }

    /**
     * Retirer le statut par défaut n'a pas d'effet : on désigne une autre adresse par défaut à la place.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(UserAddress $userAddress, array $data): UserAddress
    {
        DB::transaction(function () use ($userAddress, $data): void {
            if (isset($data['address'])) {
                $userAddress->address?->update($data['address']);
            }

            if (($data['is_default'] ?? false) && ! $userAddress->is_default) {
                $this->clearDefault($userAddress->user()->firstOrFail());
                $userAddress->is_default = true;
            }

            if (isset($data['label'])) {
                $userAddress->label = $data['label'];
            }

            $userAddress->save();
        });

        return $userAddress->load('address');
    }

    /**
     * Les réservations gardent leur propre copie de l'adresse : la supprimer du carnet ne les touche pas.
     */
    public function delete(UserAddress $userAddress): void
    {
        DB::transaction(function () use ($userAddress): void {
            $userAddress->delete();
            Address::query()->whereKey($userAddress->address_id)->delete();

            if ($userAddress->is_default) {
                UserAddress::query()
                    ->where('user_id', $userAddress->user_id)
                    ->latest()
                    ->orderByDesc('id')
                    ->first()
                    ?->update(['is_default' => true]);
            }
        });
    }

    private function clearDefault(User $user): void
    {
        $user->addresses()->where('is_default', true)->update(['is_default' => false]);
    }
}
