<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationMember>
 */
class OrganizationMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'status' => OrganizationMemberStatusEnum::ACTIVE,
            'invited_at' => now(),
            'responded_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => OrganizationMemberStatusEnum::PENDING,
            'responded_at' => null,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => OrganizationMemberStatusEnum::DECLINED,
        ]);
    }

    public function withRole(OrganizationRoleEnum $role, ?string $activityId = null): static
    {
        return $this->afterCreating(function (OrganizationMember $member) use ($role, $activityId): void {
            $member->roles()->create([
                'organization_id' => $member->organization_id,
                'role' => $role,
                'activity_id' => $activityId,
            ]);
        });
    }
}
