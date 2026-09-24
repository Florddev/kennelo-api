<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Subscription\PlanLimitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly PlanLimitService $planLimits,
    ) {}

    /**
     * @return Collection<int, OrganizationMember>
     */
    public function members(Organization $organization): Collection
    {
        return $organization->members()
            ->where('status', '!=', OrganizationMemberStatusEnum::DECLINED)
            ->with(['user.media', 'roles'])
            ->oldest()
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, OrganizationMember>
     */
    public function pendingInvitations(User $user): Collection
    {
        return OrganizationMember::query()
            ->whereBelongsTo($user)
            ->pending()
            ->whereHas('organization')
            ->with('organization')
            ->latest('invited_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Invite une personne qui a déjà un compte Kennelo. Une invitation refusée peut être renouvelée.
     */
    public function invite(Organization $organization, User $inviter, string $email): OrganizationMember
    {
        $invitee = User::where('email', $email)->first();

        if ($invitee === null) {
            throw ValidationException::withMessages(['email' => __('team.no_account')]);
        }

        $member = DB::transaction(function () use ($organization, $inviter, $invitee): OrganizationMember {
            $existing = $organization->members()->whereBelongsTo($invitee)->lockForUpdate()->first();

            if ($existing !== null && $existing->status === OrganizationMemberStatusEnum::ACTIVE) {
                throw ValidationException::withMessages(['email' => __('team.already_member')]);
            }

            if ($existing !== null && $existing->isPending()) {
                throw ValidationException::withMessages(['email' => __('team.already_invited')]);
            }

            $this->planLimits->assertCanAddMember($organization);

            $member = $existing ?? $organization->members()->make(['user_id' => $invitee->id]);

            $member->fill([
                'status' => OrganizationMemberStatusEnum::PENDING,
                'invited_by' => $inviter->id,
                'invited_at' => now(),
                'responded_at' => null,
            ])->save();

            return $member;
        });

        $this->notifications->notify($invitee, NotificationTypeEnum::MEMBER_INVITED, $this->payload($organization));

        return $member->load(['user.media', 'roles']);
    }

    public function accept(OrganizationMember $member): OrganizationMember
    {
        return $this->respond($member, OrganizationMemberStatusEnum::ACTIVE, NotificationTypeEnum::MEMBER_ACCEPTED);
    }

    public function decline(OrganizationMember $member): OrganizationMember
    {
        return $this->respond($member, OrganizationMemberStatusEnum::DECLINED, NotificationTypeEnum::MEMBER_DECLINED);
    }

    /**
     * Remplace l'ensemble des rôles du membre. Une liste vide lui retire tous ses droits,
     * sans le retirer de l'équipe.
     *
     * @param  list<array{role: string, activity_id?: string|null}>  $roles
     */
    public function syncRoles(OrganizationMember $member, array $roles): OrganizationMember
    {
        if ($member->isOwner()) {
            throw ValidationException::withMessages(['roles' => __('team.owner_has_no_roles')]);
        }

        DB::transaction(function () use ($member, $roles): void {
            $member->roles()->delete();

            foreach ($roles as $role) {
                $member->roles()->create([
                    'organization_id' => $member->organization_id,
                    'role' => OrganizationRoleEnum::from($role['role']),
                    'activity_id' => $role['activity_id'] ?? null,
                ]);
            }
        });

        return $member->load(['user.media', 'roles']);
    }

    /**
     * Retire un membre de l'équipe, ou annule son invitation. Ses rôles disparaissent avec lui.
     */
    public function remove(OrganizationMember $member): void
    {
        if ($member->isOwner()) {
            throw ValidationException::withMessages(['member' => __('team.owner_cannot_leave')]);
        }

        $member->delete();
    }

    private function respond(OrganizationMember $member, OrganizationMemberStatusEnum $status, NotificationTypeEnum $notification): OrganizationMember
    {
        if (! $member->isPending()) {
            throw ValidationException::withMessages(['invitation' => __('team.invitation_not_pending')]);
        }

        $member->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        $organization = $member->organization;

        // Le propriétaire peut être suspendu : il n'est alors plus notifiable.
        if ($organization->owner !== null) {
            $this->notifications->notify($organization->owner, $notification, array_merge($this->payload($organization), [
                'member_id' => $member->id,
                'user_id' => $member->user_id,
            ]));
        }

        return $member->load(['organization', 'roles']);
    }

    /**
     * @return array<string, string>
     */
    private function payload(Organization $organization): array
    {
        return [
            'organization_id' => $organization->id,
            'organization_name' => $organization->legal_name,
        ];
    }
}
