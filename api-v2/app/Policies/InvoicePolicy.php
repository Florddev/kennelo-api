<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Une facture se lit par son client, par l'équipe de l'entreprise qui l'a émise ou reçue (finance.view) et par
 * Kennelo. Les autres reçoivent une 404.
 */
class InvoicePolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Invoice $invoice): Response
    {
        if ($invoice->recipient_user_id === $user->id || $user->hasRole('admin')) {
            return Response::allow();
        }

        $organization = $invoice->issuerOrganization ?? $invoice->recipientOrganization;

        if ($organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, OrganizationPermissionEnum::FINANCE_VIEW, $organization)
            ? Response::allow()
            : Response::deny();
    }
}
