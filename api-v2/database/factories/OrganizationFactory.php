<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationLegalFormEnum;
use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationStatusEnum;
use App\Enums\VatRegimeEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une entreprise créée par la factory a toujours la ligne membre de son propriétaire, comme en vrai.
 *
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $siren = fake()->unique()->numerify('#########');

        return [
            'owner_id' => User::factory(),
            'legal_name' => fake()->company(),
            'legal_form' => OrganizationLegalFormEnum::COMPANY,
            'siren' => $siren,
            'siret' => $siren.fake()->numerify('#####'),
            'vat_regime' => VatRegimeEnum::STANDARD,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Organization $organization): void {
            $organization->members()->create([
                'user_id' => $organization->owner_id,
                'status' => OrganizationMemberStatusEnum::ACTIVE,
                'responded_at' => now(),
            ]);
        });
    }

    public function individual(): static
    {
        return $this->state(fn (): array => [
            'legal_form' => OrganizationLegalFormEnum::INDIVIDUAL,
            'siren' => null,
            'siret' => null,
            'vat_regime' => VatRegimeEnum::FRANCHISE,
        ]);
    }

    public function verified(): static
    {
        return $this->afterMaking(function (Organization $organization): void {
            $organization->forceFill([
                'status' => OrganizationStatusEnum::VERIFIED,
                'verified_at' => now(),
            ]);
        });
    }

    /**
     * Compte Stripe Connect activé : l'entreprise peut encaisser et recevoir ses versements.
     */
    public function withStripe(): static
    {
        return $this->afterMaking(function (Organization $organization): void {
            $organization->forceFill([
                'stripe_account_id' => 'acct_'.fake()->unique()->bothify('????????????'),
                'stripe_charges_enabled' => true,
                'stripe_payouts_enabled' => true,
                'stripe_onboarding_completed' => true,
            ]);
        });
    }
}
